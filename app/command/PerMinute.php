<?php
/*
 * @Author: panzhide seanzhui@qq.com
 * @Date: 2023-05-07 19:51:00
 * @LastEditors: panzhide 906437098@qq.com
 * @LastEditTime: 2024-07-05 16:31:51
 * @FilePath: \ciku_php\app\command\PerMinute.php
 * @Description: 这是默认设置,请设置`customMade`, 打开koroFileHeader查看配置 进行设置: https://github.com/OBKoro1/koro1FileHeader/wiki/%E9%85%8D%E7%BD%AE
 */

declare(strict_types=1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\facade\Db;
use app\common\model\mall\Goods;
class PerMinute extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('per_minute')->setDescription('每分钟执行脚本，将此脚本添加到任务计划每分钟执行');
    }

    protected function execute(Input $input, Output $output)
    {
        $cache_key = 'per_minute_cache_key'; // 缓存键名
        $time = time();
        $cache_time = cache($cache_key);
        wlog(['time' => $time, 'cache_time' => $cache_time], '每分钟执行脚本开始前' . $time, 'per_minute.log');
        if ($cache_time && $cache_time > $time - 60) { // 如果缓存存在，并且缓存时间在1分钟内，说明脚本正在运行，终止脚本
            dump('上个分钟执行脚本还在执行中');
            wlog(['time' => $time, 'cache_time' => $cache_time], '每分钟执行脚本并发', 'per_minute_err.log');
            return false;
        }
        cache($cache_key, $time); // 记录缓存，避免并发



        // 商品新增
        Db::startTrans();
        try {
            Goods::build()->addRedisGoods();
            Db::commit();
            dump('商品新增');
        } catch (\Exception $e) {
            dump($e->getMessage());
            wlog($e->getMessage(), '商品新增失败', 'per_minute_err.log');
            Db::rollback();
        }



        wlog([], '每分钟执行脚本成功' . $time, 'per_minute.log');
        cache($cache_key, null); // 清除缓存
        // 指令输出
        $output->writeln('脚本执行成功');
    }
}
