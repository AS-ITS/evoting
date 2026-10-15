#!/usr/bin/env php
<?php
/**
 * Rebuild vendor/yiisoft/extensions.php from Composer installed packages.
 *
 * Use when yiisoft/yii2-composer left an empty/incomplete extensions.php
 * (plugin activated after packages were already extracted).
 *
 * Mirrors yiisoft/yii2-composer Installer::addPackage / generateDefaultAlias.
 */
declare(strict_types=1);

$root = dirname(__DIR__);
$vendorDir = $root . '/vendor';
$outFile = $vendorDir . '/yiisoft/extensions.php';
$installedFile = $vendorDir . '/composer/installed.json';

if (!is_file($installedFile)) {
    fwrite(STDERR, "FAIL: missing $installedFile\n");
    exit(1);
}

$raw = json_decode((string) file_get_contents($installedFile), true);
if (!is_array($raw)) {
    fwrite(STDERR, "FAIL: cannot parse installed.json\n");
    exit(1);
}
$packages = $raw['packages'] ?? $raw;
if (!is_array($packages)) {
    fwrite(STDERR, "FAIL: unexpected installed.json shape\n");
    exit(1);
}

$extensions = [];
foreach ($packages as $package) {
    if (($package['type'] ?? '') !== 'yii2-extension') {
        continue;
    }
    $name = $package['name'] ?? '';
    if ($name === '') {
        continue;
    }

    $entry = [
        'name' => $name,
        'version' => $package['version'] ?? '0.0.0.0',
    ];

    $aliases = [];
    $autoload = $package['autoload'] ?? [];
    if (!empty($autoload['psr-0']) && is_array($autoload['psr-0'])) {
        foreach ($autoload['psr-0'] as $ns => $path) {
            $nsPath = str_replace('\\', '/', trim((string) $ns, '\\'));
            $rel = is_string($path) ? $path : '';
            $aliases['@' . $nsPath] = '<vendor-dir>/' . $name . '/' . trim($rel, '/') . '/' . $nsPath;
        }
    }
    if (!empty($autoload['psr-4']) && is_array($autoload['psr-4'])) {
        foreach ($autoload['psr-4'] as $ns => $path) {
            if (is_array($path)) {
                continue;
            }
            $nsPath = str_replace('\\', '/', trim((string) $ns, '\\'));
            $rel = trim((string) $path, '/');
            $aliases['@' . $nsPath] = '<vendor-dir>/' . $name . ($rel !== '' ? '/' . $rel : '');
        }
    }
    if ($aliases !== []) {
        $entry['alias'] = $aliases;
    }

    $extra = $package['extra'] ?? [];
    if (!empty($extra['bootstrap'])) {
        $entry['bootstrap'] = $extra['bootstrap'];
    }

    $extensions[$name] = $entry;
}

ksort($extensions);

if (!is_dir(dirname($outFile))) {
    mkdir(dirname($outFile), 0777, true);
}

$exported = str_replace("'<vendor-dir>", '$vendorDir . \'', var_export($extensions, true));
$content = "<?php\n\n\$vendorDir = dirname(__DIR__);\n\nreturn $exported;\n";
file_put_contents($outFile, $content);

if (function_exists('opcache_invalidate')) {
    @opcache_invalidate($outFile, true);
}

$count = count($extensions);
$hasBs5 = isset($extensions['yiisoft/yii2-bootstrap5']);
fwrite(STDOUT, "rebuild-yii-extensions: wrote $count extension(s) to $outFile\n");
if (!$hasBs5) {
    fwrite(STDERR, "WARN: yiisoft/yii2-bootstrap5 not found as yii2-extension in installed.json\n");
    exit(2);
}
exit(0);
