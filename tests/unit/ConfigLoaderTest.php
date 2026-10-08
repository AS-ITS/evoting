<?php

namespace tests\unit;

use app\config\ConfigLoader;
use app\components\KeyContextProvider;
use Codeception\Test\Unit;
use yii\base\InvalidConfigException;

class ConfigLoaderTest extends Unit
{
    public function testEncryptDecryptGeneric()
    {
        $loader = new ConfigLoader([
            'secretKey' => 'your-secret-key',
            'encryptionMethod' => 'AES-256-CBC',
        ]);

        $originalData = '測試資料123';
        $encrypted = $loader->encryptGeneric($originalData);
        $decrypted = $loader->decryptGeneric($encrypted);

        $this->assertEquals($originalData, $decrypted, "加密與解密應還原原始資料");
    }

    public function testAes256CbcHmacSha256EncryptDecrypt()
    {
        // 模擬 ConfigLoader 物件配置
        $loader = new ConfigLoader([
            'secretKey' => 'your-secret-key',
            // encryptionMethod 對 AES-256-CBC-HMAC-SHA256 方式可使用特定處理流程
            'encryptionMethod' => 'AES-256-CBC-HMAC-SHA256',
        ]);
        
        $originalData = '這是一段測試資料1234567890';
        // 加密
        $encrypted = $loader->encryptAes256CbcHmacSha256($originalData);
        
        // 解密
        $decrypted = $loader->decryptAes256CbcHmacSha256($encrypted);
        
        // 斷言還原的資料應與原始資料一致
        $this->assertEquals($originalData, $decrypted, "AES-256-CBC-HMAC-SHA256 加解密測試失敗");
    }
    
    public function testAes256CbcHmacSha256HmacVerificationFails()
    {
        $loader = new ConfigLoader([
            'secretKey' => 'your-secret-key',
            // 此處加密方法設定為 AES-256-CBC-HMAC-SHA256
            'encryptionMethod' => 'AES-256-CBC-HMAC-SHA256',
        ]);
    
        $originalData = '這是一段測試資料1234567890';
        // 取得正確的加密結果
        $encrypted = $loader->encryptAes256CbcHmacSha256($originalData);

        // 將加密結果解碼，再故意修改 HMAC 部分（例如修改第一個字元）
        $decoded = base64_decode($encrypted);
        // 取得 IV 長度
        $ivLength = KeyContextProvider::getCipherIvLength();
        // 修改 HMAC 的第一個位元組，使其不匹配
        $tamperedDecoded = substr_replace($decoded, chr(ord($decoded[$ivLength]) ^ 0xFF), $ivLength, 1);
        $tamperedEncrypted = base64_encode($tamperedDecoded);

        // 期待在解密時拋出 InvalidConfigException
        $this->expectException(\yii\base\InvalidConfigException::class);
        $loader->decryptAes256CbcHmacSha256($tamperedEncrypted);
    }
}