<?php
namespace app\models;

use Yii;
use app\components\LogSanitizer;
use app\components\helper\ArrayHelper;
use app\components\helper\UserAgent;
use app\interfaces\LogInterface;
use yii\helpers\Json;

/**
 * 投票紀錄
 *
 * @property int $id
 * @property int $voteID 投票識別碼
 * @property int $type log種類
 * @property string $user 使用者
 * @property string $ip 使用者IP
 * @property string $platform 作業系統
 * @property string $browser 瀏覽器
 * @property string $context 內文
 * @property string $created_at
 */
class Logs extends \yii\db\ActiveRecord implements LogInterface
{
    /**
     * @var string
     */
    public $createdFrom;

    /**
     * @var string
     */
    public $createdTo;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'logs';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['type', 'user', 'ip', 'browser', 'context'], 'required'],
            // [['context'], 'string'],
            [['created_at'], 'safe'],
            [['type'], 'string', 'max' => 3],
            [['voteID'], 'string', 'max' => 30],
            [['user'], 'string', 'max' => 30],
            [['ip'], 'string', 'max' => 15],
            [['browser'], 'string', 'max' => 20],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'voteID' => '投票識別碼',
            'type' => '類型',
            'user' => '使用者',
            'ip' => '使用者IP',
            'browser' => '瀏覽器',
            'context' => '內文',
            'created_at' => '建立時間',
            'createdFrom' => '起始時間',
            'createdTo' => '結束時間',
        ];
    }
    
    /**
     * 行內搜尋
     *
     * @param  array $params
     * @return \yii\db\ActiveQuery
     */
    public function search($params = [])
    {
        $query = self::find()->orderBy(['id' => SORT_DESC]);

        // 行內搜尋
        $this->load($params);

        // 取得日期範圍參數，避免 undefined array key 錯誤
        $formName = $this->formName();
        $createdFrom = $params[$formName]['createdFrom'] ?? null;
        $createdTo = $params[$formName]['createdTo'] ?? null;

        $query->andFilterWhere(['=', 'type', $this->type])
            ->andFilterWhere(['=', 'user', $this->user])
            ->andFilterWhere(['=', 'ip', $this->ip])
            ->andFilterWhere(['>=', 'created_at', $createdFrom])
            ->andFilterWhere(['<=', 'created_at', $createdTo]);

        return $query;
    }
    
    /**
     * 新增 Log 紀錄
     *
     * @param int $type 類型
     * @param mixed $context 紀錄內容
     * 
     * @return void
     */
    public static function add($type, $context, $attributes=[])
    {
        $log = new self;
        $log->voteID = ArrayHelper::getValue($attributes, 'voteID', null);
        $log->type = $type;
        $log->user = Yii::$app->user->isGuest ? 'Client' : Yii::$app->user->id;
        $log->ip = Yii::$app->request->userIP;
        $log->browser = UserAgent::getBrowserName(Yii::$app->request->userAgent);
        $log->context = LogSanitizer::sanitizeContext($context);
        $log->save();
        if (!empty($log->errors)) {
            $session = Yii::$app->session;
            foreach($log->errors as $message)
            {
                $session->addFlash('error', $message[0]);
            }
            Yii::$app->session->addFlash('warning', 'Log 紀錄建立失敗');
        }
    }

    /**
     * Console 操作稽核（不依賴 web user / session）
     *
     * @param string $type LogInterface 常數
     * @param array|string $context
     * @param array $attributes
     * @return bool 是否寫入成功
     */
    public static function addConsole($type, $context, array $attributes = []): bool
    {
        $log = new self;
        $log->voteID = ArrayHelper::getValue($attributes, 'voteID', null);
        $log->type = $type;
        $log->user = self::resolveConsoleUser();
        $log->ip = '127.0.0.1';
        $log->browser = 'console';
        $log->context = LogSanitizer::sanitizeContext(is_string($context) ? $context : Json::encode($context));
        if (!$log->save()) {
            Yii::warning('Console log insert failed: ' . Json::encode($log->errors), __METHOD__);
            return false;
        }

        return true;
    }

    private static function resolveConsoleUser(): string
    {
        foreach (['SUDO_USER', 'USER', 'USERNAME'] as $key) {
            $value = getenv($key);
            if ($value !== false && $value !== '') {
                return 'console:' . $value;
            }
        }

        return 'console';
    }

    /**
     * 簡易防護
     *
     * @param string $voteID 投票識別碼
     * @param int $limitedTime 簡易保護時間
     * @param int $limited 簡易保護時間範圍
     * 
     * @return null|int 如果為 null 表示無需等待，如果為數字表示需等待秒數
     */
    public static function getPasswordFailWait($voteID, $limitedTime=300, $limited=3)
    {
        if (!self::shouldApplyAnonLoginRateLimit()) {
            return null;
        }
        $query = self::find()->where([
            'type'   => self::LOGIN_PASSWORD_FAIL,
            'voteID' => $voteID,
            'ip' => Yii::$app->request->userIP,
        ]);
        $query->andWhere(['>=', 'created_at', date('Y-m-d H:i:s',time()-$limitedTime)]);
        $query->orderBy(['id' => SORT_DESC]);
        $q = clone $query;
        if($q->count() >= $limited)
        {
            $records = $query->all();
            $data = array_pop($records);
            $allowTime = $limitedTime-(time()-strtotime($data->created_at));
            return $allowTime;
        }
        return null;
    }

    /**
     * 匿名登入 rate limit 是否適用（正式環境預設一律限制；非正式可設定 ANON_LOGIN_RATE_LIMIT_BYPASS_CIDRS）
     */
    private static function shouldApplyAnonLoginRateLimit(): bool
    {
        $env = strtolower(getenv('APP_ENV') ?: '');
        $isProduction = in_array($env, ['production', 'prod', 'product'], true);

        $configured = array_filter(array_map('trim', explode(',', getenv('ANON_LOGIN_RATE_LIMIT_BYPASS_CIDRS') ?: '')));
        $bypassPrefixes = $configured;
        // 非正式環境若未設定 env，僅 localhost (127.0.x.x) 可 bypass，不再預設院內 /16
        if (!$isProduction && empty($bypassPrefixes)) {
            $bypassPrefixes = ['127.0'];
        }

        if (empty($bypassPrefixes)) {
            return true;
        }

        $ip = explode('.', Yii::$app->request->userIP ?? '');
        if (count($ip) >= 2) {
            $prefix = $ip[0] . '.' . $ip[1];
            if (in_array($prefix, $bypassPrefixes, true)) {
                return false;
            }
        }

        return true;
    }
}
