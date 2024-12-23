<?php
/*
 * @Author: juneChen && junechen_0606@163.com
 * @Date: 2022-12-07 09:40:47
 * @LastEditors: juneChen && junechen_0606@163.com
 * @LastEditTime: 2022-12-15 15:42:44
 * @Description: 
 * 
 * Copyright (c) 2022 by juneChen, All Rights Reserved. 
 */

namespace modules\database_backup;

use app\admin\model\AdminRule;
use app\common\library\Menu;
use ba\Filesystem;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;

class DatabaseBackup
{
    private string $uid = 'database_backup';

    /**
     * 安装模块时执行的方法
     *
     * @return void
     * @throws Throwable
     * @author juneChen <junechen_0606@163.com>
     */
    public function install(): void
    {
        $pMenu = AdminRule::where('name', 'security')->value('id');
        $menu  = [
            [
                'pid'       => $pMenu ? $pMenu : 0,
                'type'      => 'menu',
                'title'     => '数据库备份',
                'name'      => 'security/databaseBackup',
                'path'      => 'security/databaseBackup',
                'icon'      => 'el-icon-Coin',
                'menu_type' => 'tab',
                'component' => '/src/views/backend/security/DatabaseBackup/index.vue',
                'keepalive' => '1',
                'children'  => []
            ]
        ];
        Menu::create($menu);
    }

    /**
     * 卸载模块时执行的方法
     *
     * @return void
     * @throws Throwable
     * @author juneChen <junechen_0606@163.com>
     */
    public function uninstall(): void
    {
        Menu::delete('security/databaseBackup', true);
    }

    /**
     *  启用模块时执行的方法
     *
     * @return void
     * @throws Throwable
     * @author juneChen <junechen_0606@163.com>
     */
    public function enable(): void
    {
        Menu::enable('security/databaseBackup');
    }

    /**
     * 禁用模块时执行的方法
     *
     * @return void
     * @throws Throwable
     * @author juneChen <junechen_0606@163.com>
     */
    public function disable(): void
    {
        Menu::disable('security/databaseBackup');
    }

    /**
     * 模块更新方法
     * @throws Throwable
     */
    public function update(): void
    {
        // 兼容系统v1.1.2的语言包按需加载
        // 寻找安装时备份中的lang/pages文件，如果有，还原到lang/backend内而不是原位置
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
                Filesystem::fsFit('web\src\lang\pages\zh-cn\databaseBackup.ts'),
                Filesystem::fsFit('web\src\lang\pages\en\databaseBackup.ts')
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
                if (!$item->isDir() && in_array($ebakFile, $oldBaInputs)) {
                    $newFileName = str_replace(DIRECTORY_SEPARATOR . 'pages' . DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR . 'backend' . DIRECTORY_SEPARATOR, $ebakFile);
                    copy($item, root_path() . $newFileName);
                }
            }
        }
        Filesystem::delDir($zipDir);
        Filesystem::delEmptyDir(root_path() . 'web/src/lang/pages/en');
        Filesystem::delEmptyDir(root_path() . 'web/src/lang/pages/zh-cn');
    }
}
