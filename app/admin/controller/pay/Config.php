<?php

namespace app\admin\controller\pay;

use ba\PayLib;
use ba\Filesystem;
use think\facade\Config as sysConfig;
use app\common\controller\Backend;

class Config extends Backend
{
    protected static string $payConfigFile = 'pay.php';

    protected array $boolKey = [
        'enable'
    ];

    protected array $intKey = [
        'connect_timeout',
        'timeout',
        'max_file',
    ];

    public function getConfigKey()
    {
        $pay = sysConfig::get('pay');

        // 其他
        $other = array_merge($pay['logger'], $pay['http']);
        $other = $this->getValue($other);

        $this->success('', [
            'ali'   => $this->getValue($pay['alipay']['default']),
            'wx'    => $this->getValue($pay['wechat']['default']),
            'other' => $other,
        ]);
    }

    public function saveConfig()
    {
        $type = $this->request->get('type', '');
        $data = $this->request->post();
        if (!$type) {
            $this->error(__('Parameter error'));
        }
        $pay              = sysConfig::get('pay');
        $payConfigPath    = Filesystem::fsFit(config_path() . self::$payConfigFile);
        $payConfigContent = @file_get_contents($payConfigPath);

        $data = $this->setValue($data);
        if (in_array($type, ['alipay', 'wechat'])) {
            $keys = $pay[$type]['default'];
        } else {
            $keys = array_merge($pay['logger'], $pay['http']);
        }

        foreach ($keys as $key => $item) {
            if (in_array($key, $this->boolKey)) {
                $item             = $item ? 'true' : 'false';
                $payConfigContent = preg_replace("/'$key'(\s+)=>(\s+)$item, #$type#/", "'$key'\$1=>\$2$data[$key], #$type#", $payConfigContent);
            } elseif (in_array($key, $this->intKey)) {
                $payConfigContent = preg_replace("/'$key'(\s+)=> $item, #$type#/", "'$key'\$1=> $data[$key], #$type#", $payConfigContent);
            } else {
                $item             = str_replace(['/', '[', ']', '.', '('], ['\/', '\[', '\]', '\.', '\('], $item);
                $data[$key]       = str_replace('\\', '/', $data[$key]);
                $payConfigContent = preg_replace("/'$key'(\s+)=>(\s+)'$item', #$type#/", "'$key'\$1=>\$2'$data[$key]', #$type#", $payConfigContent);
            }
        }

        $result = @file_put_contents($payConfigPath, $payConfigContent);
        if (!$result) {
            $this->error(__('Configuration write failed: %s', ['config/' . self::$payConfigFile]));
        }
        $this->success();
    }

    public function getValue($arr): array
    {
        $arr = PayLib::arrayConvert($arr);
        $arr = PayLib::getFullPath($arr);
        foreach ($arr as $key => $item) {
            if (in_array($key, $this->boolKey)) {
                $arr[$key] = $item ? '1' : '0';
            }
            if (in_array($key, $this->intKey)) {
                $arr[$key] = (int)$item;
            }
        }
        return $arr;
    }

    public function setValue($arr): array
    {
        foreach ($arr as $key => $item) {
            if (in_array($key, $this->boolKey)) {
                $arr[$key] = $item ? 'true' : 'false';
            }
            if (in_array($key, $this->intKey)) {
                $arr[$key] = (int)$item;
            }
            if (array_key_exists($key, PayLib::$fileKey) && PayLib::$fileKey[$key] == 'string') {
                $arr[$key] = str_replace(root_path(), '', Filesystem::fsFit($item));
            }
            if (array_key_exists($key, PayLib::$certFileKey)) {
                if (PayLib::$certFileKey[$key] == 'string' && (str_ends_with($item, '.pem') || str_ends_with($item, '.crt'))) {
                    $arr[$key] = str_replace(root_path(), '', Filesystem::fsFit($item));
                } elseif (PayLib::$certFileKey[$key] == 'array' && !empty($item)) {
                    foreach ($item as $arrDataKey => $arrDataItem) {
                        if (!empty($arrDataItem['value']) && (str_ends_with($arrDataItem['value'], '.pem') || str_ends_with($arrDataItem['value'], '.crt'))) {
                            $arrDataItem['value'] = str_replace(root_path(), '', Filesystem::fsFit($arrDataItem['value']));
                        }
                        $item[$arrDataKey] = $arrDataItem;
                    }
                    $arr[$key] = $item;
                }
            }
            if (in_array($key, PayLib::$arrayKey)) {
                $arr[$key] = json_encode($item, JSON_UNESCAPED_UNICODE);
            }
        }
        return $arr;
    }
}