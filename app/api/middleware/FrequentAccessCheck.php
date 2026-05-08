<?php

/**
 * @Descripttion : foxCMS 是一款高效的 PHP 多端跨平台内容管理系统
 * @Author : Peter
 * @Date : 2023/7/8   17:10
 * @version : V1.08
 * @copyright : ©2026 zongshibupin 版权所有
 * @LastEditTime : 2023/7/8   17:10
 */

namespace app\api\middleware;

use app\api\controller\ConstantCode;

// 频繁访问拦截
class FrequentAccessCheck
{
    public function handle($request, \Closure $next)
    {
        $accessIp = getAccessIP();
        if (!$this->isFrequentAccess($accessIp)) {
            error("访问频繁");
        }
        return $next($request);
    }

    private function isFrequentAccess($accessIp)
    {
        try {
            $value = saveToCache($accessIp);
            if ($value) {
                if ($value < ConstantCode::$frequent_access_count) {
                    saveToCache($accessIp, ($value + 1), ConstantCode::$frequent_access_time);
                    return true;
                } else {
                    return false;
                }
            } else {
                saveToCache($accessIp, 1, ConstantCode::$frequent_access_time);
                return true;
            }
        } catch (\Exception $e) {
            return true;
        }
    }
}