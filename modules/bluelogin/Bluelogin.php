<?php

namespace modules\bluelogin;

use Throwable;
use ba\Filesystem;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class Bluelogin
{
    private string $uid = 'bluelogin';

    /**
     * 更新
     * @throws Throwable
     */
    public function update(): void
    {
        // 兼容系统v1.1.2的语言包按需加载
        // 寻找安装时备份中的 lang/pages 文件，如果有，还原到 lang/backend 内而不是原位置

        $ebakDir = root_path() . 'modules' . DIRECTORY_SEPARATOR . 'ebak' . DIRECTORY_SEPARATOR;
        $zipFile = $ebakDir . $this->uid . '-install.zip';
        $zipDir  = false;
        if (is_file($zipFile)) {
            try {
                $zipDir = Filesystem::unzip($zipFile);
            } catch (Throwable) {
                // skip
            }
        }
        if ($zipDir) {
            $oldBaInputs = [
                Filesystem::fsFit('web\src\lang\pages\zh-cn\adminLogin.ts'),
                Filesystem::fsFit('web\src\lang\pages\en\adminLogin.ts')
            ];
            foreach ($oldBaInputs as $oldBaInput) {
                @unlink(root_path() . $oldBaInput);
            }

            foreach (
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($zipDir, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                ) as $item
            ) {
                $ebakFile = Filesystem::fsFit($iterator->getSubPathName());
                if (!$item->isDir() && $ebakFile == $oldBaInputs[0]) {
                    copy($item, root_path() . 'web/src/lang/backend/zh-cn/login.ts');
                }
                if (!$item->isDir() && $ebakFile == $oldBaInputs[1]) {
                    copy($item, root_path() . 'web/src/lang/backend/en/login.ts');
                }
            }
        }
        Filesystem::delDir($zipDir);
        Filesystem::delEmptyDir(root_path() . 'web/src/lang/pages/en');
        Filesystem::delEmptyDir(root_path() . 'web/src/lang/pages/zh-cn');

        $oldBaInputs = [
            Filesystem::fsFit('web\src\lang\backend\zh-cn\adminLogin.ts'),
            Filesystem::fsFit('web\src\lang\backend\en\adminLogin.ts')
        ];
        foreach ($oldBaInputs as $oldBaInput) {
            @unlink(root_path() . $oldBaInput);
        }
    }

}