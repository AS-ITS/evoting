<?php

namespace app\tests\unit\models;

use Yii;
use app\models\Passwd;

/**
 * Passwd 模型測試
 * 測試密碼生成和加密解密功能
 */
class PasswdTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    /**
     * @var Passwd
     */
    protected $passwd;

    protected function _before()
    {
        $this->passwd = new Passwd();
    }

    // ==================== 常數測試 ====================

    /**
     * 測試：類型常數存在
     */
    public function testTypeConstantsExist()
    {
        $this->assertEquals('int', Passwd::TYPE_INT);
        $this->assertEquals('en', Passwd::TYPE_EN);
        $this->assertEquals('mix', Passwd::TYPE_MIX);
        $this->assertEquals('mixExcl', Passwd::TYPE_MIX_EXCL);
        $this->assertEquals('mixLower', Passwd::TYPE_MIX_LOWER);
        $this->assertEquals('mixUpper', Passwd::TYPE_MIX_UPPER);
    }

    /**
     * 測試：正規表示式靜態屬性存在
     */
    public function testRegexPatternsExist()
    {
        $this->assertEquals('#[0-9]+#', Passwd::$regex_09);
        $this->assertEquals('#[a-z]+#', Passwd::$regex_az);
        $this->assertEquals('#[A-Z]+#', Passwd::$regex_AZ);
    }

    /**
     * 測試：預設長度為 10
     */
    public function testDefaultLength()
    {
        $this->assertEquals(10, Passwd::$length);
    }

    // ==================== genShuffleStr() 基本測試 ====================

    /**
     * 測試：生成單一密碼返回字串
     */
    public function testGenShuffleStrSingleReturnsString()
    {
        $result = $this->passwd->genShuffleStr(1, 6);

        $this->assertIsString($result, 'Single password should be string');
        $this->assertEquals(6, strlen($result), 'Password should be 6 characters');
    }

    /**
     * 測試：生成多個密碼返回陣列
     */
    public function testGenShuffleStrMultipleReturnsArray()
    {
        $result = $this->passwd->genShuffleStr(5, 6);

        $this->assertIsArray($result, 'Multiple passwords should be array');
        $this->assertCount(5, $result, 'Should generate 5 passwords');
    }

    /**
     * 測試：最小長度為 6
     */
    public function testGenShuffleStrMinimumLength()
    {
        // 即使指定 4，也應該生成 6 位數
        $result = $this->passwd->genShuffleStr(1, 4);

        $this->assertEquals(6, strlen($result), 'Minimum length should be 6');
    }

    /**
     * 測試：使用預設長度
     */
    public function testGenShuffleStrDefaultLength()
    {
        $result = $this->passwd->genShuffleStr(1, null);

        $this->assertEquals(Passwd::$length, strlen($result), 'Should use default length');
    }

    // ==================== genShuffleStr() 類型測試 ====================

    /**
     * 測試：TYPE_INT 只包含數字
     */
    public function testGenShuffleStrTypeInt()
    {
        $result = $this->passwd->genShuffleStr(1, 8, Passwd::TYPE_INT);

        $this->assertMatchesRegularExpression('/^[0-9]+$/', $result, 'TYPE_INT should only contain digits');
    }

    /**
     * 測試：TYPE_EN 只包含英文字母
     */
    public function testGenShuffleStrTypeEn()
    {
        $result = $this->passwd->genShuffleStr(1, 8, Passwd::TYPE_EN);

        $this->assertMatchesRegularExpression('/^[a-zA-Z]+$/', $result, 'TYPE_EN should only contain letters');
    }

    /**
     * 測試：TYPE_MIX 包含數字和英文
     */
    public function testGenShuffleStrTypeMix()
    {
        $result = $this->passwd->genShuffleStr(1, 10, Passwd::TYPE_MIX);

        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9]+$/', $result, 'TYPE_MIX should contain alphanumeric');
    }

    /**
     * 測試：TYPE_MIX_EXCL 排除易混淆字元
     */
    public function testGenShuffleStrTypeMixExcl()
    {
        // 生成多個密碼以提高覆蓋率
        $results = $this->passwd->genShuffleStr(100, 10, Passwd::TYPE_MIX_EXCL);

        foreach ($results as $result) {
            // 不應包含 b、o、l、I、O、0、1
            $this->assertDoesNotMatchRegularExpression('/[bolIO01]/', $result,
                'TYPE_MIX_EXCL should not contain confusing characters: ' . $result);
        }
    }

    /**
     * 測試：TYPE_MIX_LOWER 只有小寫和數字，排除 b、o、l、0、1
     */
    public function testGenShuffleStrTypeMixLower()
    {
        $results = $this->passwd->genShuffleStr(50, 10, Passwd::TYPE_MIX_LOWER);

        foreach ($results as $result) {
            // 應只包含小寫字母和數字 2-9
            $this->assertMatchesRegularExpression('/^[a-z2-9]+$/', $result,
                'TYPE_MIX_LOWER should only contain lowercase and digits 2-9');
            // 排除 b、o、l、0、1
            $this->assertDoesNotMatchRegularExpression('/[bol01]/', $result,
                'TYPE_MIX_LOWER should not contain b, o, l, 0, 1: ' . $result);
        }
    }

    /**
     * 測試：TYPE_MIX_UPPER 只有大寫和數字
     */
    public function testGenShuffleStrTypeMixUpper()
    {
        $results = $this->passwd->genShuffleStr(50, 10, Passwd::TYPE_MIX_UPPER);

        foreach ($results as $result) {
            // 應只包含大寫字母和數字，排除 I、O、0、1
            $this->assertMatchesRegularExpression('/^[A-Z2-9]+$/', $result,
                'TYPE_MIX_UPPER should only contain uppercase and digits 2-9');
            $this->assertDoesNotMatchRegularExpression('/[IO01]/', $result,
                'TYPE_MIX_UPPER should not contain I, O, 0, 1: ' . $result);
        }
    }

    // ==================== genShuffleStr() 格式測試 ====================

    /**
     * 測試：使用格式生成密碼
     */
    public function testGenShuffleStrWithFormat()
    {
        // 格式：i=數字, s=小寫, S=大寫, 其他=原字元
        $format = 'ii-ss-SS';
        $result = $this->passwd->genShuffleStr(1, 8, Passwd::TYPE_MIX, $format);

        $this->assertEquals(8, strlen($result), 'Length should match format length');
        $this->assertEquals('-', $result[2], 'Third character should be dash');
        $this->assertEquals('-', $result[5], 'Sixth character should be dash');
    }

    /**
     * 測試：格式長度不符時使用隨機
     */
    public function testGenShuffleStrFormatLengthMismatch()
    {
        // 格式長度 (5) 與密碼長度 (8) 不符，應使用隨機生成
        $format = 'iisss';
        $result = $this->passwd->genShuffleStr(1, 8, Passwd::TYPE_MIX, $format);

        $this->assertEquals(8, strlen($result), 'Should use random generation when format length mismatches');
    }

    // ==================== genShuffleStr() 唯一性測試 ====================

    /**
     * 測試：生成的密碼是唯一的
     */
    public function testGenShuffleStrUnique()
    {
        $results = $this->passwd->genShuffleStr(100, 10);

        $unique = array_unique($results);
        $this->assertCount(count($results), $unique, 'All generated passwords should be unique');
    }

    // ==================== encrypt() / decrypt() 測試 ====================

    /**
     * 測試：加密返回字串
     */
    public function testEncryptReturnsString()
    {
        $plaintext = 'test_password';
        $encrypted = $this->passwd->encrypt($plaintext);

        $this->assertIsString($encrypted, 'Encrypted result should be string');
        $this->assertNotEquals($plaintext, $encrypted, 'Encrypted should differ from plaintext');
    }

    /**
     * 測試：加密後可正確解密
     */
    public function testEncryptDecryptRoundTrip()
    {
        $plaintext = 'my_secret_password_123';
        $encrypted = $this->passwd->encrypt($plaintext);
        $decrypted = $this->passwd->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted, 'Decrypted should match original plaintext');
    }

    /**
     * 測試：相同明文加密結果相同（使用固定金鑰）
     */
    public function testEncryptDeterministic()
    {
        $plaintext = 'consistent_password';

        $encrypted1 = $this->passwd->encrypt($plaintext);
        $encrypted2 = $this->passwd->encrypt($plaintext);

        $this->assertEquals($encrypted1, $encrypted2, 'Same plaintext should produce same ciphertext');
    }

    /**
     * 測試：不同明文加密結果不同
     */
    public function testEncryptDifferentPlaintexts()
    {
        $encrypted1 = $this->passwd->encrypt('password1');
        $encrypted2 = $this->passwd->encrypt('password2');

        $this->assertNotEquals($encrypted1, $encrypted2, 'Different plaintexts should produce different ciphertexts');
    }

    /**
     * 測試：空字串加密解密
     */
    public function testEncryptDecryptEmptyString()
    {
        $plaintext = '';
        $encrypted = $this->passwd->encrypt($plaintext);
        $decrypted = $this->passwd->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted, 'Empty string should encrypt and decrypt correctly');
    }

    /**
     * 測試：特殊字元加密解密
     */
    public function testEncryptDecryptSpecialCharacters()
    {
        $plaintext = '密碼!@#$%^&*()_+-=[]{}|;:\'",.<>?/\\`~';
        $encrypted = $this->passwd->encrypt($plaintext);
        $decrypted = $this->passwd->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted, 'Special characters should encrypt and decrypt correctly');
    }

    /**
     * 測試：長密碼加密解密
     */
    public function testEncryptDecryptLongPassword()
    {
        $plaintext = str_repeat('a', 1000);
        $encrypted = $this->passwd->encrypt($plaintext);
        $decrypted = $this->passwd->decrypt($encrypted);

        $this->assertEquals($plaintext, $decrypted, 'Long password should encrypt and decrypt correctly');
    }

    /**
     * 測試：使用自訂密碼應拋出例外
     */
    public function testEncryptWithCustomPasswordThrowsException()
    {
        $this->expectException(\yii\base\InvalidConfigException::class);
        $this->passwd->encrypt('test', 'custom_password');
    }

    /**
     * 測試：解密使用自訂密碼應拋出例外
     */
    public function testDecryptWithCustomPasswordThrowsException()
    {
        $encrypted = $this->passwd->encrypt('test');

        $this->expectException(\yii\base\InvalidConfigException::class);
        $this->passwd->decrypt($encrypted, 'custom_password');
    }

    /**
     * 測試：解密無效密文
     */
    public function testDecryptInvalidCiphertext()
    {
        // 無效的 base64 解碼後無法正確解密
        $result = $this->passwd->decrypt('invalid_ciphertext_data');

        // 應該返回 false 或空字串（依實作而定）
        $this->assertTrue(
            $result === false || $result === '',
            'Decrypting invalid ciphertext should return false or empty string'
        );
    }

    // ==================== 整合測試 ====================

    /**
     * 測試：生成密碼後加密解密
     */
    public function testGenerateAndEncryptDecrypt()
    {
        // 生成密碼
        $generated = $this->passwd->genShuffleStr(1, 8);

        // 加密
        $encrypted = $this->passwd->encrypt($generated);

        // 解密
        $decrypted = $this->passwd->decrypt($encrypted);

        $this->assertEquals($generated, $decrypted, 'Generated password should survive encrypt/decrypt cycle');
    }

    /**
     * 測試：批量生成並加密解密
     */
    public function testBatchGenerateAndEncryptDecrypt()
    {
        $passwords = $this->passwd->genShuffleStr(10, 8);

        foreach ($passwords as $password) {
            $encrypted = $this->passwd->encrypt($password);
            $decrypted = $this->passwd->decrypt($encrypted);

            $this->assertEquals($password, $decrypted, 'Each password should survive encrypt/decrypt cycle');
        }
    }

    // ==================== BVA：密碼長度邊界值 ====================

    /**
     * BVA：密碼長度 5（min-1），應被強制為 6
     */
    public function testGenShuffleStrLength5EnforcedTo6()
    {
        $result = $this->passwd->genShuffleStr(1, 5);
        $this->assertEquals(6, strlen($result), 'Length 5 should be enforced to minimum 6');
    }

    /**
     * BVA：密碼長度 6（min 邊界），應生成 6 位
     */
    public function testGenShuffleStrExactMinLength()
    {
        $result = $this->passwd->genShuffleStr(1, 6);
        $this->assertEquals(6, strlen($result), 'Length 6 should produce 6-char password');
    }

    /**
     * BVA：密碼長度 7（min+1）
     */
    public function testGenShuffleStrLength7()
    {
        $result = $this->passwd->genShuffleStr(1, 7);
        $this->assertEquals(7, strlen($result), 'Length 7 should produce 7-char password');
    }

    /**
     * BVA：密碼長度 20（較大值）
     */
    public function testGenShuffleStrLength20()
    {
        $result = $this->passwd->genShuffleStr(1, 20);
        $this->assertEquals(20, strlen($result), 'Length 20 should produce 20-char password');
    }

    // ==================== BVA：密碼產生數量邊界值 ====================

    /**
     * BVA：產生數量 2（min+1），應返回陣列
     */
    public function testGenShuffleStrCount2ReturnsArray()
    {
        $result = $this->passwd->genShuffleStr(2, 8);
        $this->assertIsArray($result, 'Count 2 should return array');
        $this->assertCount(2, $result, 'Should generate exactly 2 passwords');
    }

    // ==================== EP：格式字串邊界測試 ====================

    /**
     * EP：純數字格式
     */
    public function testGenShuffleStrFormatAllDigits()
    {
        $format = 'iiiiii';
        $result = $this->passwd->genShuffleStr(1, 6, Passwd::TYPE_MIX, $format);

        $this->assertEquals(6, strlen($result), 'All-digit format should produce 6-char password');
        $this->assertMatchesRegularExpression('/^[0-9]+$/', $result, 'All-digit format should produce only digits');
    }

    /**
     * EP：純小寫格式
     */
    public function testGenShuffleStrFormatAllLower()
    {
        $format = 'ssssss';
        $result = $this->passwd->genShuffleStr(1, 6, Passwd::TYPE_MIX, $format);

        $this->assertEquals(6, strlen($result), 'All-lower format should produce 6-char password');
        $this->assertMatchesRegularExpression('/^[a-z]+$/', $result, 'All-lower format should produce only lowercase');
    }

    /**
     * EP：純大寫格式
     */
    public function testGenShuffleStrFormatAllUpper()
    {
        $format = 'SSSSSS';
        $result = $this->passwd->genShuffleStr(1, 6, Passwd::TYPE_MIX, $format);

        $this->assertEquals(6, strlen($result), 'All-upper format should produce 6-char password');
        $this->assertMatchesRegularExpression('/^[A-Z]+$/', $result, 'All-upper format should produce only uppercase');
    }

    // ==================== EP：截斷密文解密測試 ====================

    /**
     * EP：截斷密文解密不應造成異常
     */
    public function testDecryptTruncatedCiphertext()
    {
        $encrypted = $this->passwd->encrypt('test_password');
        $truncated = substr($encrypted, 0, intval(strlen($encrypted) / 2));

        $result = $this->passwd->decrypt($truncated);
        $this->assertTrue(
            $result === false || $result === '' || $result !== 'test_password',
            'Truncated ciphertext should not decrypt to original'
        );
    }

    /**
     * EP：空字串密文解密
     */
    public function testDecryptEmptyCiphertext()
    {
        $result = $this->passwd->decrypt('');
        $this->assertTrue(
            $result === false || $result === '',
            'Empty ciphertext should return false or empty string'
        );
    }

    // ==================== #22 EP：損壞密文解密加強 ====================

    /**
     * EP：非 base64 特殊字元密文
     */
    public function testDecryptNonBase64SpecialChars()
    {
        $result = $this->passwd->decrypt('!!!@@@###$$$%%%^^^&&&');
        $this->assertTrue(
            $result === false || $result === '',
            'Non-base64 special chars should not decrypt successfully'
        );
    }

    /**
     * EP：極短密文（1 字元）
     */
    public function testDecryptSingleCharCiphertext()
    {
        $result = $this->passwd->decrypt('A');
        $this->assertTrue(
            $result === false || $result === '',
            'Single char ciphertext should not decrypt to valid result'
        );
    }

    /**
     * EP：密文中插入 null byte
     */
    public function testDecryptCiphertextWithNullByte()
    {
        $encrypted = $this->passwd->encrypt('test_password');
        // 在密文中間插入 null byte
        $midpoint = intval(strlen($encrypted) / 2);
        $corrupted = substr($encrypted, 0, $midpoint) . "\0" . substr($encrypted, $midpoint + 1);

        $result = $this->passwd->decrypt($corrupted);
        $this->assertTrue(
            $result === false || $result === '' || $result !== 'test_password',
            'Ciphertext with null byte should not decrypt to original'
        );
    }

    // ==================== EP：密碼產生數量邊界值 ====================

    /**
     * EP：產生數量 0 → 回傳空陣列
     */
    public function testGenShuffleStrCountZero()
    {
        $result = $this->passwd->genShuffleStr(0, 8);
        $this->assertEquals([], $result, 'Count 0 should return empty array');
    }

    /**
     * EP：產生數量 -1 → 回傳空陣列
     */
    public function testGenShuffleStrCountNegative()
    {
        $result = $this->passwd->genShuffleStr(-1, 8);
        $this->assertEquals([], $result, 'Negative count should return empty array');
    }

    // ==================== EP：無效密碼類型 ====================

    /**
     * EP：無效類型字串應使用預設（MIX_EXCL）
     */
    public function testGenShuffleStrInvalidType()
    {
        $result = $this->passwd->genShuffleStr(1, 8, 'invalid_type');
        $this->assertIsString($result, 'Invalid type should still return string');
        $this->assertEquals(8, strlen($result), 'Invalid type should still produce correct length');
    }

    /**
     * EP：空字串類型應使用預設（MIX_EXCL）
     */
    public function testGenShuffleStrEmptyType()
    {
        $result = $this->passwd->genShuffleStr(1, 8, '');
        $this->assertIsString($result, 'Empty type should still return string');
        $this->assertEquals(8, strlen($result), 'Empty type should still produce correct length');
    }

    /**
     * EP：null 類型應使用預設（MIX_EXCL）
     */
    public function testGenShuffleStrNullType()
    {
        $result = $this->passwd->genShuffleStr(1, 8, null);
        $this->assertIsString($result, 'Null type should still return string');
        $this->assertEquals(8, strlen($result), 'Null type should still produce correct length');
    }

    // ==================== crypto v1 ====================

    public function testEncryptV1RoundTrip()
    {
        $plain = 'v1_secret_42';
        $encrypted = $this->passwd->encryptV1($plain);
        $decrypted = $this->passwd->decrypt($encrypted, null, null, Passwd::CRYPTO_V1);

        $this->assertEquals($plain, $decrypted);
    }

    public function testEncryptV1IsNonDeterministic()
    {
        $plain = 'same_plaintext';
        $a = $this->passwd->encryptV1($plain);
        $b = $this->passwd->encryptV1($plain);

        $this->assertNotEquals($a, $b);
        $this->assertEquals($plain, $this->passwd->decrypt($a, null, null, Passwd::CRYPTO_V1));
        $this->assertEquals($plain, $this->passwd->decrypt($b, null, null, Passwd::CRYPTO_V1));
    }

    public function testComputeLookupIsDeterministic()
    {
        $voteID = 'TestVote2026';
        $plain = 'abc123';

        $this->assertEquals(
            $this->passwd->computeLookup($voteID, $plain),
            $this->passwd->computeLookup($voteID, $plain)
        );
        $this->assertNotEquals(
            $this->passwd->computeLookup($voteID, $plain),
            $this->passwd->computeLookup($voteID, 'other')
        );
    }

    public function testPackForStorageV1()
    {
        $pack = $this->passwd->packForStorage('pack_me', 'VoteA', Passwd::CRYPTO_V1);

        $this->assertSame(Passwd::CRYPTO_V1, $pack['crypto_version']);
        $this->assertNotEmpty($pack['passwd_lookup']);
        $this->assertEquals(
            'pack_me',
            $this->passwd->decrypt($pack['passwd'], null, null, Passwd::CRYPTO_V1)
        );
        $this->assertEquals(
            $pack['passwd_lookup'],
            $this->passwd->computeLookup('VoteA', 'pack_me')
        );
    }
}
