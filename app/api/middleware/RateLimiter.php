<?php

declare(strict_types=1);
/**
 * API 限流中间件
 *
 * 防止 API 被恶意刷请求，支持：
 * - 基于 IP 的限流
 * - 基于路径的限流策略
 * - 返回限流头信息供客户端参考
 *
 * @package app\api\middleware
 * @author  FoxCMS Team
 * @version 1.0
 */
namespace app\api\middleware;

use app\api\lib\ResponseHelper;
use think\facade\Cache;
use think\Request;
use think\Response;

/**
 * 接口限流中间件
 *
 * 实现请求频率限制，保护 API 服务免受滥用
 *
 * @example
 * ```php
 * // 配置限流策略
 * $rateLimits = [
 *     'default' => ['limit' => 60, 'window' => 60],
 *     'search' => ['limit' => 30, 'window' => 60],
 * ];
 * ```
 */
class RateLimiter
{
    /**
     * CORS 响应头
     *
     * 定义允许跨域访问的响应头
     *
     * @var array
     */
    protected $header = [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, OPTIONS',
        'Access-Control-Allow-Headers' => 'Authorization, Content-Type, X-Requested-With, Accept, Origin, X-Signature, X-Timestamp',
        'Access-Control-Expose-Headers' => 'X-RateLimit-Limit, X-RateLimit-Remaining, X-RateLimit-Reset',
        'Access-Control-Max-Age' => '86400',
    ];

    /**
     * 限流配置
     *
     * 定义不同接口类型的限流策略
     * - limit: 时间窗口内允许的最大请求数
     * - window: 时间窗口秒数
     *
     * @var array
     */
    protected $rateLimits = [
        'default' => ['limit' => 60, 'window' => 60],   // 默认：60次/分钟
        'search' => ['limit' => 30, 'window' => 60],     // 搜索：30次/分钟
        'detail' => ['limit' => 100, 'window' => 60],    // 详情：100次/分钟
    ];

    /**
     * 缓存键前缀
     *
     * @var string
     */
    protected $keyPrefix = 'api_rate:';

    /**
     * 处理请求
     *
     * 执行限流检查，通过则继续，失败则返回限流响应
     *
     * @param Request $request 请求对象
     * @param \Closure $next 下一个中间件
     * @return Response
     */
    public function handle($request, \Closure $next): Response
    {
        // 获取请求路径
        $path = $request->pathinfo();

        // 获取客户端 IP
        $clientIp = $this->getClientIp();

        // 生成限流缓存键
        $key = $this->resolveKey($path, $clientIp);

        // 根据路径解析限流配置
        $config = $this->resolveConfig($path);

        // 执行限流检查
        $result = $this->checkRateLimit($key, $config);

        // 如果超过限制，返回限流响应
        if ($result['limited']) {
            $response = ResponseHelper::rateLimit('Rate limit exceeded, please try again later');
            return $this->addRateLimitHeaders($response, $result);
        }

        // 执行后续处理
        $response = $next($request);

        // 在响应中添加限流头信息
        return $this->addRateLimitHeaders($response, $result);
    }

    /**
     * 获取客户端 IP 地址
     *
     * 优先从 X-Forwarded-For 获取，支持代理后的真实 IP
     *
     * @return string IP 地址
     */
    protected function getClientIp(): string
    {
        $ip = request()->ip();
        return $ip ?: '127.0.0.1';
    }

    /**
     * 解析限流缓存键
     *
     * 根据路径和 IP 生成唯一的限流键
     *
     * @param string $path 请求路径
     * @param string $clientIp 客户端 IP
     * @return string 缓存键
     */
    protected function resolveKey(string $path, string $clientIp): string
    {
        return md5($clientIp . ':' . $path);
    }

    /**
     * 解析限流配置
     *
     * 根据请求路径匹配对应的限流策略
     *
     * @param string $path 请求路径
     * @return array 限流配置 [limit, window]
     */
    protected function resolveConfig(string $path): array
    {
        // 搜索接口使用更严格的限制
        if (strpos($path, 'search') !== false) {
            return $this->rateLimits['search'];
        }

        // 详情接口限制相对宽松
        if (strpos($path, 'detail') !== false) {
            return $this->rateLimits['detail'];
        }

        // 默认限制
        return $this->rateLimits['default'];
    }

    /**
     * 检查限流
     *
     * 使用缓存实现计数器限流算法
     *
     * @param string $key 缓存键
     * @param array $config 限流配置
     * @return array 检查结果
     */
    protected function checkRateLimit(string $key, array $config): array
    {
        $fullKey = $this->keyPrefix . $key;
        $now = time();

        // 获取当前计数
        $count = Cache::get($fullKey, 0);

        // 判断是否超过限制
        if ($count >= $config['limit']) {
            return [
                'limited' => true,                      // 是否超限
                'limit' => $config['limit'],             // 限制数量
                'remaining' => 0,                        // 剩余请求数
                'reset' => $now + $config['window']     // 重置时间戳
            ];
        }

        // 第一请求，设置缓存
        if ($count === 0) {
            Cache::set($fullKey, 1, $config['window']);
        } else {
            // 递增计数
            Cache::inc($fullKey);
        }

        // 返回未超限的结果
        return [
            'limited' => false,
            'limit' => $config['limit'],
            'remaining' => max(0, $config['limit'] - $count - 1),
            'reset' => $now + $config['window']
        ];
    }

    /**
     * 添加限流响应头
     *
     * 在响应头中添加限流相关信息，供客户端参考
     *
     * @param Response $response 响应对象
     * @param array $result 检查结果
     * @return Response
     */
    protected function addRateLimitHeaders(Response $response, array $result): Response
    {
        $response->header([
            // X-RateLimit-Limit: 时间窗口内允许的最大请求数
            'X-RateLimit-Limit' => $result['limit'],
            // X-RateLimit-Remaining: 剩余可用请求数
            'X-RateLimit-Remaining' => $result['remaining'],
            // X-RateLimit-Reset: 限流重置的时间戳
            'X-RateLimit-Reset' => $result['reset'],
        ]);

        return $response;
    }
}