<?php

namespace modules\pay;

use Throwable;
use think\facade\Cache;
use app\common\model\Config;
use app\common\library\Menu;
use app\admin\model\AdminRule;

class Pay
{
    /**
     * 安装
     * @throws Throwable
     */
    public function install(): void
    {
        $routineMenu = AdminRule::where('name', 'routine')->value('id');
        $menu        = [
            [
                'type'      => 'menu',
                'title'     => '支付配置',
                'name'      => 'pay/config',
                'path'      => 'pay/config',
                'icon'      => 'el-icon-Tools',
                'menu_type' => 'tab',
                'component' => '/src/views/backend/pay/config.vue',
                'keepalive' => '1',
                'pid'       => $routineMenu ?? 0,
                'children'  => [
                    ['type' => 'button', 'title' => '查看', 'name' => 'pay/config/getConfigKey'],
                    ['type' => 'button', 'title' => '修改配置', 'name' => 'pay/config/saveConfig'],
                ]
            ]
        ];
        Menu::create($menu);
    }

    /**
     * 卸载
     * @throws Throwable
     */
    public function uninstall(): void
    {
        Menu::delete('pay/config', true);
    }

    /**
     * 启用
     * @throws Throwable
     */
    public function enable(): void
    {
        Menu::enable('pay/config');
        // 恢复配置
        $config = Cache::pull('pay-module-config');
        if ($config) {
            @file_put_contents(config_path() . 'pay.php', $config);
        }
        Config::addQuickEntrance('支付配置', 'pay/config');
    }

    /**
     * 禁用
     * @throws Throwable
     */
    public function disable(): void
    {
        Menu::disable('pay/config');
        // 备份配置
        $config = @file_get_contents(config_path() . 'pay.php');
        if ($config) {
            Cache::set('pay-module-config', $config, 3600);
        }
        Config::removeQuickEntrance('支付配置');
    }
}