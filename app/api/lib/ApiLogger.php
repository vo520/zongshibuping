<?php

declare(strict_types=1);
/**
 * API 日志记录器
 *
 * 提供完整的 API 请求/响应日志记录功能
 *
 * @package app\api\lib
 * @author  FoxCMS Team
 * @version 1.0
 */
namespace app\api\lib;

use think\facade\Db;
use think\facade\Cache;

/**
 * API 日志服务
 *
 * 统一管理 API 日志记录
 */
class ApiLogger
{
    /**
     * 日志保留天数
     *
     * @var int
     */
    protected const LOG_RETENTION_DAYS = 30;

    /**
     * 慢查询阈值（毫秒）
     *
     * @var int
     */
    protected const SLOW_QUERY_THRESHOLD = 200;

    /**
     * 日志启用状态
     *
     * @var bool
     */
    protected static $enabled = true;

    /**
     * 请求开始时间
     *
     * @var float
     */
    protected static $requestStartTime = 0;

    /**
     * 启用日志记录
     */
    public static function enable(): void
    {
        self::$enabled = true;
    }

    /**
     * 禁用日志记录
     */
    public static function disable(): void
    {
        self::$enabled = false;
    }

    /**
     * 检查日志是否启用
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return self::$enabled;
    }

    /**
     * 记录请求开始
     *
     * @param string $endpoint 请求端点
     * @param array $params 请求参数
     * @param string $ip 客户端IP
     * @return string 请求追踪ID
     */
    public static function logRequestStart(string $endpoint, array $params, string $ip): string
    {
        if (!self::$enabled) {
            return '';
        }

        self::$requestStartTime = microtime(true);

        $requestId = self::generateRequestId();

        $logData = [
            'request_id' => $requestId,
            'endpoint' => $endpoint,
            'method' => request()->method(),
            'params' => json_encode($params),
            'ip' => $ip,
            'user_agent' => request()->header('user-agent'),
            'referer' => request()->header('referer'),
            'start_time' => date('Y-m-d H:i:s'),
            'memory_usage' => memory_get_usage(true),
        ];

        // 记录到文件日志
        trace($logData, 'api_request_start');

        return $requestId;
    }

    /**
     * 记录请求完成
     *
     * @param string $requestId 请求追踪ID
     * @param int $responseCode 响应码
     * @param array $responseData 响应数据
     * @param bool $fromCache 是否来自缓存
     */
    public static function logRequestEnd(string $requestId, int $responseCode, array $responseData, bool $fromCache = false): void
    {
        if (!self::$enabled || empty($requestId)) {
            return;
        }

        $endTime = microtime(true);
        $duration = ($endTime - self::$requestStartTime) * 1000; // 转换为毫秒

        $logData = [
            'request_id' => $requestId,
            'response_code' => $responseCode,
            'response_size' => strlen(json_encode($responseData)),
            'duration_ms' => round($duration, 2),
            'from_cache' => $fromCache,
            'is_slow' => $duration > self::SLOW_QUERY_THRESHOLD,
            'end_time' => date('Y-m-d H:i:s'),
            'memory_peak' => memory_get_peak_usage(true),
        ];

        // 记录到文件日志
        trace($logData, 'api_request_end');

        // 记录慢请求警告
        if ($logData['is_slow']) {
            self::logSlowRequest($requestId, $duration, $responseCode);
        }
    }

    /**
     * 记录慢请求
     *
     * @param string $requestId 请求ID
     * @param float $duration 耗时（毫秒）
     * @param int $responseCode 响应码
     */
    public static function logSlowRequest(string $requestId, float $duration, int $responseCode): void
    {
        $logData = [
            'type' => 'slow_request',
            'request_id' => $requestId,
            'duration_ms' => $duration,
            'threshold_ms' => self::SLOW_QUERY_THRESHOLD,
            'response_code' => $responseCode,
            'endpoint' => request()->pathinfo(),
            'ip' => request()->ip(),
            'time' => date('Y-m-d H:i:s')
        ];

        // 使用独立的慢请求日志通道
        trace($logData, 'api_slow');

        // 可以添加监控告警逻辑
        self::checkAlert($logData);
    }

    /**
     * 记录错误
     *
     * @param string $requestId 请求ID
     * @param \Throwable $exception 异常对象
     * @param array $context 上下文信息
     */
    public static function logError(string $requestId, \Throwable $exception, array $context = []): void
    {
        $logData = [
            'type' => 'error',
            'request_id' => $requestId,
            'error_code' => $exception->getCode(),
            'error_message' => $exception->getMessage(),
            'error_file' => $exception->getFile(),
            'error_line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'context' => $context,
            'endpoint' => request()->pathinfo() ?? '',
            'ip' => request()->ip() ?? '',
            'time' => date('Y-m-d H:i:s')
        ];

        // 记录到错误日志
        trace($logData, 'api_error');

        // 检查是否需要告警
        self::checkAlert($logData);
    }

    /**
     * 记录数据库查询
     *
     * @param string $sql SQL语句
     * @param float $duration 执行时间（毫秒）
     * @param array $bindings 参数绑定
     */
    public static function logQuery(string $sql, float $duration, array $bindings = []): void
    {
        if (!self::$enabled || $duration < 50) { // 只记录超过50ms的查询
            return;
        }

        $logData = [
            'type' => 'query',
            'sql' => $sql,
            'duration_ms' => round($duration, 2),
            'bindings' => json_encode($bindings),
            'is_slow' => $duration > self::SLOW_QUERY_THRESHOLD,
            'time' => date('Y-m-d H:i:s')
        ];

        trace($logData, 'api_query');
    }

    /**
     * 记录缓存操作
     *
     * @param string $operation 操作类型（get/set/delete）
     * @param string $key 缓存键
     * @param bool $hit 是否命中
     * @param float $duration 操作耗时
     */
    public static function logCache(string $operation, string $key, bool $hit, float $duration = 0): void
    {
        $logData = [
            'type' => 'cache',
            'operation' => $operation,
            'key' => $key,
            'hit' => $hit,
            'duration_ms' => round($duration * 1000, 2),
            'time' => date('Y-m-d H:i:s')
        ];

        trace($logData, 'api_cache');
    }

    /**
     * 检查告警条件
     *
     * @param array $logData 日志数据
     */
    protected static function checkAlert(array $logData): void
    {
        // 错误日志告警
        if (isset($logData['type']) && $logData['type'] === 'error') {
            self::sendAlert('error', $logData);
        }

        // 慢请求告警（超过500ms）
        if (isset($logData['is_slow']) && $logData['duration_ms'] > 500) {
            self::sendAlert('slow', $logData);
        }
    }

    /**
     * 发送告警
     *
     * @param string $type 告警类型
     * @param array $data 告警数据
     */
    protected static function sendAlert(string $type, array $data): void
    {
        // 可以对接邮件、钉钉、企业微信等告警渠道
        // 这里仅记录到特定日志通道
        $alertData = array_merge($data, [
            'alert_type' => $type,
            'alert_time' => date('Y-m-d H:i:s')
        ]);

        trace($alertData, 'api_alert');
    }

    /**
     * 生成请求ID
     *
     * @return string
     */
    protected static function generateRequestId(): string
    {
        return sprintf(
            '%s-%s-%s',
            date('YmdHis'),
            substr(md5(uniqid()), 0, 8),
            substr(md5(mt_rand()), 0, 8)
        );
    }

    /**
     * 获取请求统计信息
     *
     * @param string $startDate 开始日期
     * @param string $endDate 结束日期
     * @return array
     */
    public static function getStatistics(string $startDate, string $endDate): array
    {
        // 从日志文件中分析统计数据
        $stats = [
            'totalRequests' => 0,
            'slowRequests' => 0,
            'errorRequests' => 0,
            'avgDuration' => 0,
            'maxDuration' => 0,
            'cacheHitRate' => 0,
        ];

        // 实际项目中可以从数据库或日志系统查询
        return $stats;
    }

    /**
     * 清理过期日志
     *
     * @return int 清理的日志条数
     */
    public static function cleanOldLogs(): int
    {
        $days = self::LOG_RETENTION_DAYS;
        $count = 0;

        // 清理旧日志文件的逻辑
        // 这里只是示例，实际需要根据日志存储方式实现

        return $count;
    }

    /**
     * 导出日志
     *
     * @param array $filters 过滤条件
     * @return array
     */
    public static function exportLogs(array $filters = []): array
    {
        // 实现日志导出逻辑
        return [];
    }
}