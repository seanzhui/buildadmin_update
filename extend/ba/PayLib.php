<?php

namespace ba;

class PayLib
{

    /**
     * 数组字段 key
     * @var array
     */
    public static array $arrayKey = [
        'wechat_public_cert_path',
    ];

    /**
     * 每次都需要做文件绝对路径转换的 key
     * @var array
     */
    public static array $fileKey = [
        'file' => 'string',
    ];

    /**
     * 当内容以 pem、crt 结尾时才需要做绝对路径转换的 key
     */
    public static array $certFileKey = [
        'app_secret_cert'         => 'string',
        'app_public_cert_path'    => 'string',
        'alipay_public_cert_path' => 'string',
        'alipay_root_cert_path'   => 'string',
        'mch_secret_cert'         => 'string',
        'mch_public_cert_path'    => 'string',
        'wechat_public_cert_path' => 'array',
    ];

    /**
     * 获取支付配置数据
     * @return array
     */
    public static function getConfig(): array
    {
        $config = config('pay');

        // 数组项
        $config['wechat']['default'] = self::arrayConvert($config['wechat']['default']);

        // 文件路径转换为绝对路径
        $config['logger']            = self::getFullPath($config['logger']);
        $config['alipay']['default'] = self::getFullPath($config['alipay']['default']);
        $config['wechat']['default'] = self::getFullPath($config['wechat']['default']);

        return $config;
    }

    /**
     * 文件路径转换为绝对路径
     * @param array $arr 配置数组
     * @return array
     */
    public static function getFullPath(array $arr): array
    {
        foreach ($arr as $key => $item) {
            if (array_key_exists($key, self::$fileKey) && self::$fileKey[$key] == 'string') {
                $arr[$key] = root_path() . str_replace(root_path(), '', Filesystem::fsFit($item));
            }

            if (array_key_exists($key, self::$certFileKey)) {
                if (self::$certFileKey[$key] == 'string' && (str_ends_with($item, '.pem') || str_ends_with($item, '.crt'))) {
                    $arr[$key] = root_path() . str_replace(root_path(), '', Filesystem::fsFit($item));
                } elseif (self::$certFileKey[$key] == 'array' && !empty($item)) {
                    foreach ($item as $arrDataKey => $arrDataItem) {
                        if (!empty($arrDataItem['value']) && (str_ends_with($arrDataItem['value'], '.pem') || str_ends_with($arrDataItem['value'], '.crt'))) {
                            $arrDataItem['value'] = root_path() . str_replace(root_path(), '', Filesystem::fsFit($arrDataItem['value']));
                        }
                        $item[$arrDataKey] = $arrDataItem;
                    }
                    $arr[$key] = $item;
                }
            }
        }
        return $arr;
    }

    /**
     * 数组字段转换 json=>array | array=>json
     * @param array $arr
     * @return array
     */
    public static function arrayConvert(array $arr): array
    {
        foreach ($arr as $key => $item) {
            if (in_array($key, self::$arrayKey)) {
                $item = json_decode($item, true);
                $item = $item ?: [];
                foreach ($item as &$arrDataItem) {
                    if (!empty($arrDataItem['value'])) {
                        $arrDataItem['value'] = str_replace('//', '/', $arrDataItem['value']);
                    }
                }
                $arr[$key] = $item;
            }
        }
        return $arr;
    }
}