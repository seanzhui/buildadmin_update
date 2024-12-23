<?php

namespace app\api\controller;

use Throwable;
use ba\PayLib;
use think\facade\Log;
use Yansongda\Pay\Pay;
use app\common\controller\Frontend;
use Psr\Http\Message\ResponseInterface;

class PayNotify extends Frontend
{
    protected array $noNeedLogin = ['wechat', 'alipay'];

    /**
     * 允许跨域
     * @throws Throwable
     */
    public function initialize(): void
    {
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 1800');
        header('Access-Control-Allow-Methods: *');
        header('Access-Control-Allow-Headers: *');
        header('Access-Control-Allow-Origin: *');
        parent::initialize();
    }

    /**
     * 微信支付回调
     * @throws Throwable
     */
    public function wechat(): ResponseInterface
    {
        try {
            Pay::config(PayLib::getConfig());

            $result = Pay::wechat()->callback();

            Log::write('收到回调数据：' . json_encode($result));
        } catch (Throwable $e) {
            Log::write('支付回调异常' . $e->getMessage());
        }

        return Pay::wechat()->success();
    }

    /**
     * 支付宝支付回调
     * @throws Throwable
     */
    public function alipay(): ResponseInterface
    {
        try {
            Pay::config(PayLib::getConfig());

            $result = Pay::alipay()->callback();

            Log::write('收到回调数据：' . json_encode($result));
        } catch (Throwable $e) {
            Log::write('支付回调异常' . $e->getMessage());
        }

        return Pay::alipay()->success();
    }
}