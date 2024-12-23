<?php
/*
 * @Author: panzhide seanzhui@qq.com
 * @Date: 2023-07-05 10:49:23
 * @LastEditors: panzhide 906437098@qq.com
 * @LastEditTime: 2024-07-05 16:29:15
 * @FilePath: \ciku_php\app\command\PerDay.php
 * @Description: 这是默认设置,请设置`customMade`, 打开koroFileHeader查看配置 进行设置: https://github.com/OBKoro1/koro1FileHeader/wiki/%E9%85%8D%E7%BD%AE
 */

declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use app\model\ads\AdsTriggerData;
use think\facade\Db;

class PerHour extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('per_hour')->setDescription('每小时执行脚本，将此脚本添加到任务计划每小时执行');
    }

    protected function execute(Input $input, Output $output)
    {
        $cache_key = 'per_hour_cache_key'; // 缓存键名
        $time = time();
        $cache_time = cache($cache_key);
        wlog(['time' => $time, 'cache_time' => $cache_time], '每小时执行脚本开始前' . $time, 'per_hour.log');
        if ($cache_time && $cache_time > $time - 3400) { // 如果缓存存在，并且缓存时间在1分钟内，说明脚本正在运行，终止脚本
            dump('上个每小时执行脚本还在执行中');
            wlog(['time' => $time, 'cache_time' => $cache_time], '每小时执行脚本并发', 'per_hour_err.log');
            return false;
        }
        cache($cache_key, $time); // 记录缓存，避免并发




        // 广告微信好友匹配
        Db::startTrans();
        try {
            AdsTriggerData::build()->wechatCronyMate();
            Db::commit();
            dump('广告微信好友匹配');
        } catch (\Exception $e) {
            dump($e->getMessage());
            wlog($e->getMessage(), '广告微信好友匹配处理失败', 'per_hour_err.log');
            Db::rollback();
        }


        wlog([], '每小时执行脚本成功' . $time, 'per_hour.log');
        cache($cache_key, null); // 清除缓存
        // 指令输出
        $output->writeln('脚本执行成功');
    }
}
