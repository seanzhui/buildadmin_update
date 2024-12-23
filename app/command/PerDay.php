<?php
/*
 * @Author: panzhide seanzhui@qq.com
 * @Date: 2023-07-05 10:49:23
 * @LastEditors: panzhide seanzhui@qq.com
 * @LastEditTime: 2024-01-04 13:14:16
 * @FilePath: \ciku_php\app\command\PerDay.php
 * @Description: 这是默认设置,请设置`customMade`, 打开koroFileHeader查看配置 进行设置: https://github.com/OBKoro1/koro1FileHeader/wiki/%E9%85%8D%E7%BD%AE
 */

declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use app\model\ads\AdsWechatAccountCustomers;
use think\console\Output;
use think\facade\Db;

class PerDay extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('per_day')->setDescription('每天执行脚本，将此脚本添加到任务计划天执行');
    }

    protected function execute(Input $input, Output $output)
    {
        $cache_key = 'per_day_cache_key'; // 缓存键名
        $time = time();
        $cache_time = cache($cache_key);
        wlog(['time' => $time, 'cache_time' => $cache_time], '每天执行脚本开始前' . $time, 'per_day.log');
        if ($cache_time && $cache_time > $time - 3600) { // 如果缓存存在，并且缓存时间在1分钟内，说明脚本正在运行，终止脚本
            dump('上个天执行脚本还在执行中');
            wlog(['time' => $time, 'cache_time' => $cache_time], '每天执行脚本并发', 'per_day_err.log');
            return false;
        }
        cache($cache_key, $time); // 记录缓存，避免并发


        // 获取昨天微信客户信息
        Db::startTrans();
        try {
            AdsWechatAccountCustomers::build()->getyesterdayCustomer();
            Db::commit();
            dump('新增获取昨天微信客户信息');
        } catch (\Exception $e) {
            dump($e->getMessage());
            wlog($e->getMessage(), '新增获取昨天微信客户信息失败', 'per_day_err.log');
            Db::rollback();
        }

        wlog([], '每天执行脚本成功' . $time, 'per_day.log');
        cache($cache_key, null); // 清除缓存
        // 指令输出
        $output->writeln('脚本执行成功');
    }
}
