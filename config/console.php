<?php
// +----------------------------------------------------------------------
// | 控制台配置
// +----------------------------------------------------------------------
return [
    // 指令定义
    'commands' => [
        'per_day'    => 'app\command\PerDay',
        'per_hour'   => 'app\command\PerHour',
        'per_minute' => 'app\command\PerMinute',
        'per_second' => 'app\command\PerSecond',
    ],
];
