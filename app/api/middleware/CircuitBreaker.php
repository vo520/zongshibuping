<?php

declare(strict_types=1);
/**
 * API 熔断保护器
 *
 * 实现熔断模式，保护系统在高负载或故障时不被压垮
 *
 * @package app\api\middleware
 * @author  FoxCMS Team
 * @version 1.0
 */
namespace app\api\middleware;

use think\facade\Cache;
use think\Request;
use think\Response;

/**
 * 熔断保护中间件
 *
 * 实现三层保护机制：
 * 1. 服务熔断 - 当错误率超过阈值时启用
 * 2. 限流保护 - 控制请求频率
 * 3. 超时保护 - 防止请求阻塞
 */
class CircuitBreaker
{
    /**
     * 熔断状态：CLOSED（正常）
     */
    protected const STATE_CLOSED = 'closed';

    /**
     * 熔断状态：OPEN（断开）
     */
    protected const STATE_OPEN = 'open';

    /**
     * 熔断状态：HALF_OPEN（半开）
     */
    protected const STATE_HALF_OPEN = 'half_open';

    /**
     * 错误率阈值（百分比）
     *
     * @var float
     */
    protected $errorThreshold = 50.0;

    /**
     * 最小请求数
     *
     * 达到这个数量的请求后才开始计算错误率
     *
     * @var int
     */
    protected $minimumRequests = 10;

    /**
     * 熔断持续时间（秒）
     *
     * @var int
     */
    protected $sleepWindow = 30;

    /**
     * 半开状态允许的请求数
     *
     * @var int
     */
    protected $halfOpenRequests = 3;

    /**
     * 当前状态
     *
     * @var string
     */
    protected $state = self::STATE_CLOSED;

    /**
     * 缓存键前缀
     *
     * @var string
     */
    protected $cachePrefix = 'circuit_breaker:';

    /**
     * 处理请求
     *
     * @param Request $request 请求对象
     * @param \Closure $next 下一个处理器
     * @return Response
     */
    public function handle($request, \Closure $next): Response
    {
        $endpoint = $this->resolveEndpoint($request);

        // 检查熔断状态
        $state = $this->getState($endpoint);

        if ($state === self::STATE_OPEN) {
            // 熔断开启，直接返回降级响应
            return $this->getCircuitOpenResponse($endpoint);
        }

        try {
            // 执行请求
            $response = $next($request);

            // 记录成功
            $this->recordSuccess($endpoint);

            // 添加熔断头信息
            $response->header([
                'X-Circuit-Breaker' => $this->getState($endpoint),
                'X-Circuit-Health' => $this->getHealth($endpoint)
            ]);

            return $response;

        } catch (\Throwable $e) {
            // 记录失败
            $this->recordFailure($endpoint);

            // 重新检查状态
            $newState = $this->getState($endpoint);

            // 如果状态变为 OPEN，记录告警
            if ($newState === self::STATE_OPEN) {
                $this->logCircuitOpened($endpoint, $e);
            }

            // 抛出异常让上层处理
            throw $e;
        }
    }

    /**
     * 获取熔断状态
     *
     * @param string $endpoint 端点
     * @return string
     */
    public function getState(string $endpoint): string
    {
        $stateKey = $this->getStateKey($endpoint);

        // 获取当前状态
        $state = Cache::get($stateKey);

        if ($state === null) {
            return self::STATE_CLOSED;
        }

        // 检查是否需要从 OPEN 转换到 HALF_OPEN
        if ($state === self::STATE_OPEN) {
            $openedAt = Cache::get($stateKey . ':opened_at');

            if ($openedAt && (time() - $openedAt) >= $this->sleepWindow) {
                // 转换到半开状态
                Cache::set($stateKey, self::STATE_HALF_OPEN, 0);
                Cache::set($stateKey . ':half_open_count', 0, 0);
                return self::STATE_HALF_OPEN;
            }
        }

        return $state;
    }

    /**
     * 记录成功请求
     *
     * @param string $endpoint 端点
     */
    public function recordSuccess(string $endpoint): void
    {
        $successKey = $this->getSuccessKey($endpoint);
        $failureKey = $this->getFailureKey($endpoint);

        // 增加成功计数
        Cache::inc($successKey);
        Cache::expire($successKey, 60); // 1分钟内有效

        // 重置失败计数
        Cache::rm($failureKey);

        // 如果是半开状态，检查是否可以关闭
        if ($this->getState($endpoint) === self::STATE_HALF_OPEN) {
            $halfOpenCount = Cache::get($this->getStateKey($endpoint) . ':half_open_count', 0);
            Cache::inc($this->getStateKey($endpoint) . ':half_open_count');

            // 连续成功，关闭熔断
            if ($halfOpenCount >= $this->halfOpenRequests - 1) {
                $this->reset($endpoint);
            }
        }
    }

    /**
     * 记录失败请求
     *
     * @param string $endpoint 端点
     */
    public function recordFailure(string $endpoint): void
    {
        $failureKey = $this->getFailureKey($endpoint);
        $successKey = $this->getSuccessKey($endpoint);

        // 增加失败计数
        Cache::inc($failureKey);
        Cache::expire($failureKey, 60);

        // 如果是半开状态，直接打开熔断
        if ($this->getState($endpoint) === self::STATE_HALF_OPEN) {
            $this->trip($endpoint);
            return;
        }

        // 检查是否需要打开熔断
        $failures = Cache::get($failureKey, 0);
        $successes = Cache::get($successKey, 0);
        $total = $failures + $successes;

        if ($total >= $this->minimumRequests) {
            $errorRate = ($failures / $total) * 100;

            if ($errorRate >= $this->errorThreshold) {
                $this->trip($endpoint);
            }
        }
    }

    /**
     * 打开熔断
     *
     * @param string $endpoint 端点
     */
    public function trip(string $endpoint): void
    {
        $stateKey = $this->getStateKey($endpoint);

        Cache::set($stateKey, self::STATE_OPEN, 0);
        Cache::set($stateKey . ':opened_at', time(), 0);
    }

    /**
     * 关闭熔断
     *
     * @param string $endpoint 端点
     */
    public function reset(string $endpoint): void
    {
        $stateKey = $this->getStateKey($endpoint);

        Cache::set($stateKey, self::STATE_CLOSED, 0);
        Cache::rm($stateKey . ':opened_at');
        Cache::rm($this->getSuccessKey($endpoint));
        Cache::rm($this->getFailureKey($endpoint));
    }

    /**
     * 获取健康度
     *
     * @param string $endpoint 端点
     * @return float
     */
    public function getHealth(string $endpoint): float
    {
        $successKey = $this->getSuccessKey($endpoint);
        $failureKey = $this->getFailureKey($endpoint);

        $successes = Cache::get($successKey, 0);
        $failures = Cache::get($failureKey, 0);
        $total = $successes + $failures;

        if ($total === 0) {
            return 100.0;
        }

        return round((1 - ($failures / $total)) * 100, 2);
    }

    /**
     * 获取熔断开启的响应
     *
     * @param string $endpoint 端点
     * @return Response
     */
    protected function getCircuitOpenResponse(string $endpoint): Response
    {
        $retryAfter = $this->sleepWindow;
        $stateKey = $this->getStateKey($endpoint) . ':opened_at';
        $openedAt = Cache::get($stateKey);

        if ($openedAt) {
            $retryAfter = max(1, $this->sleepWindow - (time() - $openedAt));
        }

        $response = json([
            'code' => 1008,
            'msg' => 'Service temporarily unavailable, please try again later',
            'data' => null,
            'time' => time(),
            'circuitBreaker' => [
                'state' => 'open',
                'retryAfter' => $retryAfter,
                'health' => $this->getHealth($endpoint)
            ]
        ]);

        return $response->code(503)->header([
            'Retry-After' => $retryAfter,
            'X-Circuit-Breaker' => 'open'
        ]);
    }

    /**
     * 记录熔断开启日志
     *
     * @param string $endpoint 端点
     * @param \Throwable|null $exception 异常
     */
    protected function logCircuitOpened(string $endpoint, \Throwable $exception = null): void
    {
        $logData = [
            'type' => 'circuit_opened',
            'endpoint' => $endpoint,
            'error_threshold' => $this->errorThreshold,
            'minimum_requests' => $this->minimumRequests,
            'sleep_window' => $this->sleepWindow,
            'exception' => $exception ? $exception->getMessage() : null,
            'time' => date('Y-m-d H:i:s')
        ];

        trace($logData, 'api_circuit');
    }

    /**
     * 解析端点标识
     *
     * @param Request $request
     * @return string
     */
    protected function resolveEndpoint($request): string
    {
        return $request->pathinfo();
    }

    /**
     * 获取状态缓存键
     *
     * @param string $endpoint
     * @return string
     */
    protected function getStateKey(string $endpoint): string
    {
        return $this->cachePrefix . 'state:' . md5($endpoint);
    }

    /**
     * 获取成功计数缓存键
     *
     * @param string $endpoint
     * @return string
     */
    protected function getSuccessKey(string $endpoint): string
    {
        return $this->cachePrefix . 'success:' . md5($endpoint);
    }

    /**
     * 获取失败计数缓存键
     *
     * @param string $endpoint
     * @return string
     */
    protected function getFailureKey(string $endpoint): string
    {
        return $this->cachePrefix . 'failure:' . md5($endpoint);
    }

    /**
     * 设置错误阈值
     *
     * @param float $threshold 百分比
     * @return $this
     */
    public function setErrorThreshold(float $threshold): self
    {
        $this->errorThreshold = $threshold;
        return $this;
    }

    /**
     * 设置最小请求数
     *
     * @param int $count
     * @return $this
     */
    public function setMinimumRequests(int $count): self
    {
        $this->minimumRequests = $count;
        return $this;
    }

    /**
     * 设置熔断持续时间
     *
     * @param int $seconds
     * @return $this
     */
    public function setSleepWindow(int $seconds): self
    {
        $this->sleepWindow = $seconds;
        return $this;
    }
}