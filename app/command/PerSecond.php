<?php
declare (strict_types = 1);

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\input\Option;
use think\console\Output;
use think\facade\Db;

class PerSecond extends Command
{
    protected function configure()
    {
        // 指令配置
        $this -> setName('per_second')-> setDescription('每秒执行脚本，将此脚本添加到任务计划秒钟执行');
    }

    protected function execute(Input $input, Output $output)
    {

        // 更新推广上报量
        Db::startTrans();
        try {
            PromoterUserData::build()->setPromoterGetNumber();
            Db::commit();
            dump('更新推广上报量');
        } catch (\Exception $e) {
            dump($e->getMessage());
            wlog($e->getMessage(), '更新推广上报量失败', 'per_minute_err.log');
            Db::rollback();
        }

        // 指令输出
        $output->writeln('脚本执行成功');
    }
}
