<?php

/**
 * 守門：應用程式碼不得再引用 Webix（MIT 樹已改用 kartik dialog / Bootstrap Modal）。
 */
class WebixRemovedTest extends \Codeception\Test\Unit
{
    public function testWebixAssetClassGone()
    {
        $this->assertFileDoesNotExist(codecept_root_dir() . '/assets/WebixAsset.php');
    }

    public function testNoPublishedWebixTree()
    {
        $root = codecept_root_dir();
        $hits = [];
        foreach (['assets', 'frontend', 'web/assets'] as $rel) {
            $dir = $root . '/' . $rel;
            if (!is_dir($dir)) {
                continue;
            }
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($it as $file) {
                if ($file->isDir() && preg_match('/^webix_/', $file->getFilename())) {
                    $hits[] = substr($file->getPathname(), strlen($root) + 1);
                }
            }
        }
        $this->assertSame([], $hits, 'Webix tree still on disk: ' . implode(', ', $hits));
    }

    public function testApplicationPhpHasNoWebixApi()
    {
        $hits = [];
        $dirs = ['views', 'assets', 'widgets', 'controllers', 'commands', 'components', 'actions'];
        foreach ($dirs as $rel) {
            $dir = codecept_root_dir() . '/' . $rel;
            if (!is_dir($dir)) {
                continue;
            }
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $file) {
                if (!$file->isFile() || strtolower($file->getExtension()) !== 'php') {
                    continue;
                }
                $path = $file->getPathname();
                $src = file_get_contents($path);
                if (preg_match('/WebixAsset|webix\.(alert|confirm|modalbox|ui)\b/', $src)) {
                    $hits[] = substr($path, strlen(codecept_root_dir()) + 1);
                }
            }
        }
        $this->assertSame([], $hits, 'Webix still referenced: ' . implode(', ', $hits));
    }
}
