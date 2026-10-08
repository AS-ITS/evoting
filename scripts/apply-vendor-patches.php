#!/usr/bin/env php
<?php
/**
 * 套用 patches/*.patch 到 vendor/。
 * composer post-install / post-update 呼叫；已套用則略過。
 */
$root = dirname(__DIR__);
$patchDir = $root . '/patches';
$files = glob($patchDir . '/*.patch') ?: [];
sort($files);

if ($files === []) {
    echo "apply-vendor-patches: no patches\n";
    exit(0);
}

$failed = 0;
foreach ($files as $patchFile) {
    $name = basename($patchFile);
    $target = $root . '/vendor/yiisoft/yii2/db/mysql/QueryBuilder.php';
    if (!is_file($target) && str_contains($name, 'QueryBuilder')) {
        echo "apply-vendor-patches: skip $name (vendor not installed)\n";
        continue;
    }

    $applied = applyWithPatchBinary($root, $patchFile);
    if ($applied === true) {
        echo "apply-vendor-patches: OK $name\n";
        continue;
    }
    if ($applied === 'already') {
        echo "apply-vendor-patches: already applied $name\n";
        continue;
    }

    $fb = applyQueryBuilderFallback($root);
    if ($fb === 'already') {
        echo "apply-vendor-patches: already applied $name\n";
        continue;
    }
    if ($fb === true) {
        echo "apply-vendor-patches: OK $name (fallback)\n";
        continue;
    }

    fwrite(STDERR, "apply-vendor-patches: WARN $name hunk not found (upstream may have fixed). See VENDOR_PATCHES.md\n");
}

exit($failed > 0 ? 1 : 0);

/**
 * @return true|false|'already'
 */
function applyWithPatchBinary(string $root, string $patchFile)
{
    $patch = findPatchBinary();
    if ($patch === null) {
        return false;
    }

    $cmd = sprintf(
        '%s -d %s -p1 -N --dry-run --silent < %s 2>/dev/null',
        escapeshellarg($patch),
        escapeshellarg($root),
        escapeshellarg($patchFile)
    );
    exec($cmd, $out, $dry);
    if ($dry === 0) {
        $cmd = sprintf(
            '%s -d %s -p1 -N --silent < %s 2>/dev/null',
            escapeshellarg($patch),
            escapeshellarg($root),
            escapeshellarg($patchFile)
        );
        exec($cmd, $out2, $code);
        return $code === 0 ? true : false;
    }

    $src = @file_get_contents($root . '/vendor/yiisoft/yii2/db/mysql/QueryBuilder.php') ?: '';
    if (str_contains($src, '$value = ($maxValue === null) ? 1 : (int)$maxValue + 1;')) {
        return 'already';
    }
    return false;
}

function findPatchBinary(): ?string
{
    foreach (['patch', '/usr/bin/patch'] as $bin) {
        $which = trim((string) shell_exec('command -v ' . escapeshellarg($bin) . ' 2>/dev/null'));
        if ($which !== '' && is_executable($which)) {
            return $which;
        }
        if ($bin[0] === '/' && is_executable($bin)) {
            return $bin;
        }
    }
    return null;
}

/** @return true|false|'already' */
function applyQueryBuilderFallback(string $root)
{
    $file = $root . '/vendor/yiisoft/yii2/db/mysql/QueryBuilder.php';
    if (!is_file($file)) {
        return false;
    }
    $src = file_get_contents($file);
    if (str_contains($src, '$value = ($maxValue === null) ? 1 : (int)$maxValue + 1;')) {
        return 'already';
    }
    $old = '                $value = $this->db->createCommand("SELECT MAX(`$key`) FROM $tableName")->queryScalar() + 1;';
    $new = "                // Fix for PHP 8.x: Handle null from MAX() when table is empty\n"
        . '                $maxValue = $this->db->createCommand("SELECT MAX(`$key`) FROM $tableName")->queryScalar();' . "\n"
        . '                $value = ($maxValue === null) ? 1 : (int)$maxValue + 1;';
    if (!str_contains($src, $old)) {
        return false;
    }
    return file_put_contents($file, str_replace($old, $new, $src)) !== false;
}
