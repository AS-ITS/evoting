#!/usr/bin/env php
<?php
/**
 * Encrypt a config plaintext with the current master key (no Yii app bootstrap).
 *
 * Usage:
 *   export VOTING_MASTER_KEY=...   # or rely on /etc/voting/master.key
 *   php scripts/encrypt-config-value.php 'plaintext-password'
 *
 * Prints Base64 ciphertext to stdout (suitable for .env DB_PASSWORD=...).
 */
declare(strict_types=1);

$root = dirname(__DIR__);
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';

use app\components\KeyContextProvider;
use app\components\KeyDerivation;
use app\components\MasterKeyLoader;

if ($argc < 2) {
    fwrite(STDERR, "Usage: php scripts/encrypt-config-value.php <plaintext>\n");
    exit(1);
}
$plain = (string) $argv[1];

try {
    $masterKey = (new MasterKeyLoader())->loadKey();
    $kd = new KeyDerivation($masterKey);
    $secret = $kd->deriveConfigKey();
    $iv = $kd->deriveConfigIv();
    $raw = KeyContextProvider::encryptData($plain, $secret, $iv);
    if ($raw === false) {
        fwrite(STDERR, "FAIL: openssl encrypt returned false\n");
        exit(2);
    }
    echo base64_encode($raw);
    exit(0);
} catch (Throwable $e) {
    fwrite(STDERR, 'FAIL: ' . $e->getMessage() . "\n");
    exit(3);
}
