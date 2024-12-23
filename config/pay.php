<?php
// +----------------------------------------------------------------------
// | 支付配置
// | #alipay# 和 #wechat# 这类标记是因为 key 可能重复，使用标记实现编程式修改配置
// +----------------------------------------------------------------------

return [
    'alipay' => [
        'default' => [
            'app_id'                  => '', #alipay#
            'app_secret_cert'         => '', #alipay#
            'app_public_cert_path'    => '', #alipay#
            'alipay_public_cert_path' => '', #alipay#
            'alipay_root_cert_path'   => '', #alipay#
            'return_url'              => '', #alipay#
            'notify_url'              => '', #alipay#
            'app_auth_token'          => '', #alipay#
            'service_provider_id'     => '', #alipay#
            'mode'                    => '0', #alipay#
        ]
    ],
    'wechat' => [
        'default' => [
            'mch_id'                  => '', #wechat#
            'mch_secret_key_v2'       => '', #wechat#
            'mch_secret_key'          => '', #wechat#
            'mch_secret_cert'         => '', #wechat#
            'mch_public_cert_path'    => '', #wechat#
            'notify_url'              => '', #wechat#
            'mp_app_id'               => '', #wechat#
            'mini_app_id'             => '', #wechat#
            'app_id'                  => '', #wechat#
            'combine_app_id'          => '', #wechat#
            'combine_mch_id'          => '', #wechat#
            'sub_mp_app_id'           => '', #wechat#
            'sub_app_id'              => '', #wechat#
            'sub_mini_app_id'         => '', #wechat#
            'sub_mch_id'              => '', #wechat#
            'wechat_public_cert_path' => '', #wechat#
            'mode'                    => '0', #wechat#
        ]
    ],
    'unipay' => [
        'default' => [
            // 必填-商户号
            'mch_id'                  => '',
            // 必填-商户公私钥
            'mch_cert_path'           => '',
            // 必填-商户公私钥密码
            'mch_cert_password'       => '',
            // 必填-银联公钥证书路径
            'unipay_public_cert_path' => '',
            // 必填
            'return_url'              => '',
            // 必填
            'notify_url'              => '',
        ],
    ],
    'logger' => [
        'enable'   => false, #other#
        'file'     => 'runtime/pay/pay.log', #other#
        'level'    => 'debug', #other#
        'type'     => 'single', #other#
        'max_file' => 30, #other#
    ],
    'http'   => [
        'timeout'         => 10, #other#
        'connect_timeout' => 10, #other#
        // 更多配置项请参考 [Guzzle](https://guzzle-cn.readthedocs.io/zh_CN/latest/request-options.html)
    ],
];