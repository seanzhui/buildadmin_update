<?php
/*
 * @Author: panzhide 906437098@qq.com
 * @Date: 2024-07-05 14:45:25
 * @LastEditors: panzhide 906437098@qq.com
 * @LastEditTime: 2024-07-05 14:46:02
 * @FilePath: \buildadmin\app\common\middleware\AllowCrossDomain.php
 * @Description: 这是默认设置,请设置`customMade`, 打开koroFileHeader查看配置 进行设置: https://github.com/OBKoro1/koro1FileHeader/wiki/%E9%85%8D%E7%BD%AE
 */
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2021 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
declare (strict_types=1);

namespace app\common\middleware;

use Closure;
use think\Request;
use think\Response;
use think\facade\Config;

/**
 * 跨域请求支持
 * 安全起见，只支持了配置中的域名
 */
class AllowCrossDomain
{
    protected array $header = [
        'Access-Control-Allow-Credentials' => 'true',
        'Access-Control-Max-Age'           => 1800,
        'Access-Control-Allow-Methods'     => 'GET, POST, PATCH, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers'     => 'think-lang, server, ba_user_token, ba-user-token, ba_token, ba-token, batoken, Authorization, Content-Type, If-Match, If-Modified-Since, If-None-Match, If-Unmodified-Since, X-CSRF-TOKEN, X-Requested-With',
        'Access-Control-Allow-Origin'      => '*',
    ];

    /**
     * 跨域请求检测
     * @access public
     * @param Request    $request
     * @param Closure    $next
     * @param array|null $header
     * @return Response
     */
    public function handle(Request $request, Closure $next, ?array $header = []): Response
    {
        $header = !empty($header) ? array_merge($this->header, $header) : $this->header;

        $origin = $request->header('origin');
        if ($origin && !isset($header['Access-Control-Allow-Origin'])) {
            $info = parse_url($origin);

            // 获取跨域配置
            $corsDomain   = explode(',', Config::get('buildadmin.cors_request_domain'));
            $corsDomain[] = $request->host(true);

            if (in_array("*", $corsDomain) || in_array($origin, $corsDomain) || (isset($info['host']) && in_array($info['host'], $corsDomain))) {
                $header['Access-Control-Allow-Origin'] = $origin;
            }
        }

        $request->allowCrossDomainHeaders = $header;

        return $next($request)->header($header);
    }
}
