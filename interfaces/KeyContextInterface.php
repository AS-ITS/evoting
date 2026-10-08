<?php
namespace app\interfaces;

/**
 * 金鑰派生上下文介面
 *
 * 定義 HKDF 金鑰派生時使用的上下文字串常數。
 * 這些常數用於 KeyDerivation::deriveKey() 的 context 參數，
 * 不是加密金鑰本身，而是用於區分不同用途的派生金鑰。
 *
 * 實際的加密金鑰來自環境變數 MASTER_KEY，
 * 這些上下文字串只是 HKDF (RFC 5869) 的 info 參數。
 *
 * @see docs/KEY_DERIVATION_RULES.md
 * @see components/KeyDerivation.php
 * @since 2026-01-21
 */
interface KeyContextInterface
{
    /********** 金鑰長度常數 **********/

    /**
     * AES-256 加密金鑰長度 (bytes)
     * @var int
     */
    const KEY_LENGTH_AES256 = 32;

    /**
     * AES CBC 模式 IV 長度 (bytes)
     * @var int
     */
    const IV_LENGTH_AES_CBC = 16;
}
