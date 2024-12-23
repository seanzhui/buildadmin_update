<?php
/*
 * @Author: panzhide 906437098@qq.com
 * @Date: 2024-07-05 15:42:20
 * @LastEditors: panzhide 906437098@qq.com
 * @LastEditTime: 2024-07-05 16:13:34
 * @FilePath: \album\app\common\service\RedisService.php
 * @Description: 这是默认设置,请设置`customMade`, 打开koroFileHeader查看配置 进行设置: https://github.com/OBKoro1/koro1FileHeader/wiki/%E9%85%8D%E7%BD%AE
 */

namespace app\common\service;

use think\Service;

class RedisService extends Service
{
    public static function build()
    {
        return new self();
    }
    private static $instance = null;

    private function __construct()
    {
        // 配置 Redis 连接参数
        $parameters = [
            'host'       => env('redis.host', '127.0.0.1'),
            'port'       => env('redis.port', 6379),
            'password'   => env('redis.password', ''),
            'timeout'    => env('redis.timeout', 0),
            'expire'     => env('redis.expire', 0),
            'prefix'     => env('redis.prefix', ''),
        ];

        try {
            self::$instance = new \Predis\Client($parameters);
        } catch (\Exception $e) {
            // 处理连接异常
            echo "Redis 连接失败: " . $e->getMessage();
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            new self();
        }
        return self::$instance;
    }
}
