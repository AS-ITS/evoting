<?php
/**
 * 極簡 Chrome DevTools Protocol 用戶端
 *
 * 只用 PHP 內建函式實作 WebSocket，不需安裝 Node.js 或其他套件。
 */
class Cdp
{
    /** @var resource */
    private $sock;
    private $id = 0;
    /** @var array 尚未被 waitEvent() 取走的事件 */
    private $events = [];

    /** @var string 分頁 target id */
    public $targetId;

    public function __construct($wsUrl)
    {
        $u = parse_url($wsUrl);
        $this->sock = stream_socket_client("tcp://{$u['host']}:{$u['port']}", $errno, $err, 10);
        if (!$this->sock) {
            throw new RuntimeException("CDP 連線失敗：$err");
        }
        $key = base64_encode(random_bytes(16));
        fwrite($this->sock, "GET {$u['path']} HTTP/1.1\r\nHost: {$u['host']}:{$u['port']}\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: $key\r\nSec-WebSocket-Version: 13\r\n\r\n");
        $resp = '';
        while (strpos($resp, "\r\n\r\n") === false) {
            $c = fread($this->sock, 1);
            if ($c === '' || $c === false) {
                throw new RuntimeException('CDP handshake 中斷');
            }
            $resp .= $c;
        }
        if (strpos($resp, ' 101 ') === false) {
            // 不把對端回應內容放進例外訊息（Checkmarx Stored XSS）
            throw new RuntimeException('CDP handshake 失敗：未收到 101 Switching Protocols');
        }
    }

    /**
     * 開新分頁並連線
     *
     * @param  string $endpoint 例如 http://127.0.0.1:9222
     * @return self
     */
    /**
     * 呼叫 DevTools HTTP 介面
     *
     * Chrome 的 DevTools HTTP 伺服器對 PHP 內建 http wrapper 不友善（HTTP/1.0 被拒、
     * HTTP/1.1 keep-alive 會等到逾時），故以 socket 送出請求並依 Content-Length 讀取。
     *
     * @return array|null 無法連線時為 null
     */
    public static function http($url, $method = 'GET', $timeout = 5)
    {
        $u = parse_url($url);
        $sock = @stream_socket_client("tcp://{$u['host']}:{$u['port']}", $errno, $err, $timeout);
        if (!$sock) {
            return null;
        }
        stream_set_timeout($sock, $timeout);
        $path = $u['path'].(isset($u['query']) ? '?'.$u['query'] : '');
        fwrite($sock, "$method $path HTTP/1.1\r\nHost: {$u['host']}:{$u['port']}\r\nContent-Length: 0\r\nConnection: close\r\n\r\n");
        $head = '';
        while (strpos($head, "\r\n\r\n") === false) {
            $c = fread($sock, 1);
            if ($c === '' || $c === false) {
                fclose($sock);
                return null;
            }
            $head .= $c;
        }
        $body = '';
        if (preg_match('/Content-Length:\s*(\d+)/i', $head, $m)) {
            while (strlen($body) < (int) $m[1]) {
                $chunk = fread($sock, (int) $m[1] - strlen($body));
                if ($chunk === '' || $chunk === false) {
                    break;
                }
                $body .= $chunk;
            }
        }
        fclose($sock);
        return json_decode($body, true);
    }

    public static function newPage($endpoint, $isolated = false)
    {
        $endpoint = rtrim($endpoint, '/');
        if (!$isolated) {
            $info = self::http($endpoint.'/json/new?about:blank', 'PUT');
            if (!$info) {
                throw new RuntimeException("無法連線 Chrome：$endpoint");
            }
            $c = new self($info['webSocketDebuggerUrl']);
            $c->targetId = $info['id'];
            return $c;
        }
        // 獨立的瀏覽器環境（類似無痕視窗）：不共用 cookie／session
        $version = self::http($endpoint.'/json/version');
        if (!$version) {
            throw new RuntimeException("無法連線 Chrome：$endpoint");
        }
        $browser = new self($version['webSocketDebuggerUrl']);
        $contextId = $browser->send('Target.createBrowserContext', ['disposeOnDetach' => false])['browserContextId'];
        $targetId = $browser->send('Target.createTarget', ['url' => 'about:blank', 'browserContextId' => $contextId])['targetId'];
        $u = parse_url($version['webSocketDebuggerUrl']);
        $c = new self("ws://{$u['host']}:{$u['port']}/devtools/page/$targetId");
        $c->targetId = $targetId;
        return $c;
    }

    private function readExact($n)
    {
        $buf = '';
        while (strlen($buf) < $n) {
            $chunk = fread($this->sock, $n - strlen($buf));
            if ($chunk === '' || $chunk === false) {
                $meta = stream_get_meta_data($this->sock);
                if ($meta['timed_out']) {
                    throw new RuntimeException('CDP 讀取逾時');
                }
                if (feof($this->sock)) {
                    throw new RuntimeException('CDP 連線已關閉');
                }
                continue;
            }
            $buf .= $chunk;
        }
        return $buf;
    }

    private function readMessage($timeout)
    {
        stream_set_timeout($this->sock, max(1, (int) $timeout));
        $msg = '';
        do {
            $h = unpack('Cb0/Cb1', $this->readExact(2));
            $fin = ($h['b0'] & 0x80) !== 0;
            $len = $h['b1'] & 0x7F;
            if ($len === 126) {
                $len = unpack('n', $this->readExact(2))[1];
            } elseif ($len === 127) {
                $len = unpack('J', $this->readExact(8))[1];
            }
            $msg .= $len ? $this->readExact($len) : '';
        } while (!$fin);
        return $msg;
    }

    private function writeMessage($data)
    {
        $len = strlen($data);
        $head = chr(0x81);
        if ($len < 126) {
            $head .= chr(0x80 | $len);
        } elseif ($len < 65536) {
            $head .= chr(0x80 | 126).pack('n', $len);
        } else {
            $head .= chr(0x80 | 127).pack('J', $len);
        }
        $mask = random_bytes(4);
        $masked = '';
        for ($i = 0; $i < $len; $i++) {
            $masked .= $data[$i] ^ $mask[$i % 4];
        }
        fwrite($this->sock, $head.$mask.$masked);
    }

    /**
     * 送出命令並等待回應
     */
    public function send($method, $params = [], $timeout = 30)
    {
        $id = ++$this->id;
        $this->writeMessage(json_encode(['id' => $id, 'method' => $method, 'params' => (object) $params], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        while (true) {
            $d = json_decode($this->readMessage($timeout), true);
            if (isset($d['id']) && $d['id'] === $id) {
                if (isset($d['error'])) {
                    throw new RuntimeException("$method: ".json_encode($d['error'], JSON_UNESCAPED_UNICODE));
                }
                return $d['result'];
            }
            if (isset($d['method'])) {
                $this->events[] = $d;
            }
        }
    }

    public function clearEvents()
    {
        $this->events = [];
    }

    /**
     * 取得（不移除）目前收到的指定事件
     */
    public function events($name)
    {
        return array_values(array_map(function ($e) { return $e['params']; }, array_filter($this->events, function ($e) use ($name) {
            return $e['method'] === $name;
        })));
    }

    /**
     * 等待指定事件
     */
    public function waitEvent($name, $timeout = 20)
    {
        $end = microtime(true) + $timeout;
        while (true) {
            foreach ($this->events as $i => $e) {
                if ($e['method'] === $name) {
                    unset($this->events[$i]);
                    return $e['params'];
                }
            }
            $left = $end - microtime(true);
            if ($left <= 0) {
                throw new RuntimeException("等待 $name 逾時");
            }
            $d = json_decode($this->readMessage((int) ceil($left)), true);
            if (isset($d['method'])) {
                $this->events[] = $d;
            }
        }
    }
}
