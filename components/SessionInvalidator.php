<?php

namespace app\components;

use DirectoryIterator;
use Yii;

/**
 * 銷毀指定 session 的檔案型 session。
 *
 * 不以「savePath + sessionId」組路徑後 unlink（Checkmarx Relative Path Traversal），
 * 改為列舉 session 目錄，對 DirectoryIterator 提供的 pathname 操作。
 */
final class SessionInvalidator
{
    public const SESSION_ID_PATTERN = '/^[a-zA-Z0-9]{26,40}$/';

    public static function isValidSessionId(string $sessionId): bool
    {
        return (bool) preg_match(self::SESSION_ID_PATTERN, $sessionId);
    }

    public static function destroyById(string $sessionId): bool
    {
        if (!preg_match(self::SESSION_ID_PATTERN, $sessionId, $matches)) {
            Yii::error(
                'Invalid session id detected: ' . substr($sessionId, 0, 8) . '***',
                __METHOD__
            );
            return false;
        }

        $safeSessionId = $matches[0];
        $expectedBasename = 'sess_' . $safeSessionId;

        $savePath = self::resolveSavePath();
        if ($savePath === null) {
            Yii::error('Session save path is unavailable', __METHOD__);
            return false;
        }

        try {
            $iterator = new DirectoryIterator($savePath);
        } catch (\UnexpectedValueException $e) {
            Yii::error('Unable to read session directory: ' . $e->getMessage(), __METHOD__);
            return false;
        }

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }
            if ($fileInfo->getFilename() !== $expectedBasename) {
                continue;
            }

            if (!@unlink($fileInfo->getPathname())) {
                Yii::error('Failed to unlink session file', __METHOD__);
                return false;
            }
            break;
        }

        $session = Yii::$app->session;
        if ($session->getIsActive() && $session->getId() === $safeSessionId) {
            $session->destroy();
        }

        return true;
    }

    public static function destroyByCreatorIds(array $creatorIds): int
    {
        $ids = [];
        foreach ($creatorIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        if ($ids === []) {
            return 0;
        }

        $savePath = self::resolveSavePath();
        if ($savePath === null) {
            return 0;
        }

        $idParam = (string) Yii::$app->anon->idParam;
        if ($idParam === '') {
            return 0;
        }

        try {
            $iterator = new DirectoryIterator($savePath);
        } catch (\UnexpectedValueException $e) {
            Yii::error('Unable to read session directory: ' . $e->getMessage(), __METHOD__);
            return 0;
        }

        $destroyed = 0;
        foreach ($iterator as $fileInfo) {
            if (!$fileInfo->isFile()) {
                continue;
            }
            $filename = $fileInfo->getFilename();
            if (!str_starts_with($filename, 'sess_')) {
                continue;
            }

            $pathname = $fileInfo->getPathname();
            $content = @file_get_contents($pathname);
            if ($content === false || $content === '') {
                continue;
            }

            foreach ($ids as $creatorId) {
                if (!self::sessionContentHasAnonId($content, $idParam, $creatorId)) {
                    continue;
                }
                if (@unlink($pathname)) {
                    $destroyed++;
                } else {
                    Yii::error('Failed to unlink session file for creator ' . $creatorId, __METHOD__);
                }
                break;
            }
        }

        return $destroyed;
    }

    public static function sessionContentHasAnonId(string $content, string $idParam, int $creatorId): bool
    {
        $asString = (string) $creatorId;
        $needleInt = $idParam . '|i:' . $creatorId . ';';
        $needleStr = $idParam . '|s:' . strlen($asString) . ':"' . $asString . '";';

        return str_contains($content, $needleInt) || str_contains($content, $needleStr);
    }

    private static function resolveSavePath(): ?string
    {
        $savePath = Yii::$app->session->savePath;
        if ($savePath === null || $savePath === '') {
            $savePath = session_save_path();
        }
        if ($savePath === null || $savePath === '') {
            return null;
        }

        $real = realpath($savePath);
        return $real === false ? null : $real;
    }
}
