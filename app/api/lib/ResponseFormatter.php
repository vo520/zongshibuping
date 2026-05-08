<?php

declare(strict_types=1);
/**
 * API 响应格式化器
 *
 * 统一 API 响应格式，提供标准化输出
 *
 * @package app\api\lib
 * @author  FoxCMS Team
 * @version 1.0
 */
namespace app\api\lib;

use app\api\status\Code;
use think\Response;

/**
 * API 响应格式化器
 *
 * 确保所有 API 响应遵循统一的格式规范
 */
class ResponseFormatter
{
    /**
     * API 版本
     *
     * @var string
     */
    public const API_VERSION = 'v1';

    /**
     * API 最低兼容版本
     *
     * @var string
     */
    public const MIN_COMPATIBLE_VERSION = 'v1';

    /**
     * 响应启用压缩的最小大小（字节）
     *
     * @var int
     */
    protected const MIN_COMPRESS_SIZE = 1024;

    /**
     * 生成成功响应
     *
     * @param mixed $data 响应数据
     * @param string|null $msg 成功消息
     * @param array $extra 额外信息
     * @return Response
     */
    public static function success($data = null, string $msg = null, array $extra = []): Response
    {
        $response = [
            'code' => Code::SUCCESS,
            'msg' => $msg ?? Code::getMessage(Code::SUCCESS),
            'data' => $data,
            'time' => time(),
            'requestId' => self::getRequestId(),
            'apiVersion' => self::API_VERSION,
        ];

        // 合并额外信息
        if (!empty($extra)) {
            $response['extra'] = $extra;
        }

        return self::buildResponse($response);
    }

    /**
     * 生成错误响应
     *
     * @param string|null $msg 错误消息
     * @param int $code 错误码
     * @param mixed $data 错误数据
     * @param array $details 错误详情
     * @return Response
     */
    public static function error(string $msg = null, int $code = Code::FAIL, $data = null, array $details = []): Response
    {
        $response = [
            'code' => $code,
            'msg' => $msg ?? Code::getMessage($code),
            'data' => $data,
            'time' => time(),
            'requestId' => self::getRequestId(),
            'apiVersion' => self::API_VERSION,
        ];

        // 添加错误详情（仅在调试模式或特定错误码时）
        if (!empty($details) && (config('app.app_debug') || $code >= 2000)) {
            $response['details'] = $details;
        }

        return self::buildResponse($response, $code >= 2000 ? 500 : 400);
    }

    /**
     * 生成分页响应
     *
     * @param array $list 数据列表
     * @param int $page 当前页
     * @param int $pageSize 每页数量
     * @param int $total 总数
     * @param array $extra 额外信息
     * @return Response
     */
    public static function paginate(array $list, int $page, int $pageSize, int $total, array $extra = []): Response
    {
        $totalPages = $total > 0 ? ceil($total / $pageSize) : 0;

        $data = [
            'list' => $list,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $total,
                'totalPages' => $totalPages,
                'hasMore' => $page * $pageSize < $total,
                'firstPage' => $page === 1,
                'lastPage' => $page >= $totalPages
            ]
        ];

        // 添加额外分页信息
        if (!empty($extra)) {
            $data['extra'] = $extra;
        }

        return self::success($data);
    }

    /**
     * 生成资源未找到响应
     *
     * @param string $resource 资源名称
     * @param mixed $id 资源ID
     * @return Response
     */
    public static function notFound(string $resource = 'Resource', $id = null): Response
    {
        $msg = "{$resource} not found";
        if ($id !== null) {
            $msg .= " (ID: {$id})";
        }

        return self::error($msg, Code::NOT_FOUND);
    }

    /**
     * 生成参数错误响应
     *
     * @param string|array $errors 错误信息
     * @return Response
     */
    public static function invalidParams($errors): Response
    {
        $msg = is_array($errors) ? implode(', ', $errors) : $errors;
        return self::error($msg, Code::INVALID_PARAM, ['errors' => $errors]);
    }

    /**
     * 生成未授权响应
     *
     * @param string|null $msg 错误消息
     * @return Response
     */
    public static function unauthorized(string $msg = null): Response
    {
        return self::error($msg ?? 'Unauthorized', Code::UNAUTHORIZED, null, ['requireAuth' => true]);
    }

    /**
     * 生成禁止访问响应
     *
     * @param string|null $msg 错误消息
     * @return Response
     */
    public static function forbidden(string $msg = null): Response
    {
        return self::error($msg ?? 'Access forbidden', Code::FORBIDDEN);
    }

    /**
     * 生成限流响应
     *
     * @param int $retryAfter 重试等待秒数
     * @return Response
     */
    public static function rateLimit(int $retryAfter = 60): Response
    {
        $response = self::error('Rate limit exceeded, please try again later', Code::RATE_LIMIT_EXCEEDED);
        $response->header([
            'Retry-After' => $retryAfter,
            'X-RateLimit-Retry-After' => $retryAfter
        ]);
        return $response;
    }

    /**
     * 生成服务器错误响应
     *
     * @param string $errorId 错误追踪ID
     * @param array $context 错误上下文
     * @return Response
     */
    public static function serverError(string $errorId, array $context = []): Response
    {
        $details = [
            'errorId' => $errorId,
            'context' => $context
        ];

        // 在调试模式下显示更多信息
        if (config('app.app_debug')) {
            $details['trace'] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        }

        return self::error('Internal server error', Code::SERVER_ERROR, ['errorId' => $errorId], $details);
    }

    /**
     * 生成版本不兼容响应
     *
     * @param string $currentVersion 当前版本
     * @param string $requiredVersion 所需版本
     * @return Response
     */
    public static function versionMismatch(string $currentVersion, string $requiredVersion): Response
    {
        return self::error('API version mismatch', 1002, [
            'currentVersion' => $currentVersion,
            'requiredVersion' => $requiredVersion,
            'upgradeUrl' => '/docs/upgrade-guide'
        ]);
    }

    /**
     * 构建响应对象
     *
     * @param array $data 响应数据
     * @param int $statusCode HTTP状态码
     * @return Response
     */
    protected static function buildResponse(array $data, int $statusCode = 200): Response
    {
        $response = json($data, $statusCode);

        // 添加 CORS 头
        $response->header([
            'Content-Type' => 'application/json; charset=utf-8',
            'X-Api-Version' => self::API_VERSION,
            'X-Response-Time' => microtime(true) - (defined('REQUEST_START_TIME') ? REQUEST_START_TIME : $_SERVER['REQUEST_TIME_FLOAT'] ?? time())
        ]);

        return $response;
    }

    /**
     * 获取请求ID
     *
     * @return string
     */
    protected static function getRequestId(): string
    {
        // 优先从请求头获取
        if (isset($_SERVER['HTTP_X_REQUEST_ID']) && !empty($_SERVER['HTTP_X_REQUEST_ID'])) {
            return $_SERVER['HTTP_X_REQUEST_ID'];
        }

        // 生成唯一ID
        return sprintf(
            'req_%s_%s',
            date('YmdHis'),
            bin2hex(random_bytes(8))
        );
    }

    /**
     * 验证 API 版本兼容性
     *
     * @param string $version 请求版本
     * @return array [bool, string|null]
     */
    public static function checkVersion(string $version): array
    {
        if (empty($version)) {
            return [true, null]; // 默认版本兼容
        }

        // 解析版本号
        $requestMajor = intval(str_replace('v', '', $version));
        $currentMajor = intval(str_replace('v', '', self::API_VERSION));

        // 主版本号必须匹配
        if ($requestMajor !== $currentMajor) {
            return [false, self::versionMismatch($version, self::API_VERSION)];
        }

        // 检查最低兼容版本
        $minMajor = intval(str_replace('v', '', self::MIN_COMPATIBLE_VERSION));
        if ($requestMajor < $minMajor) {
            return [false, self::versionMismatch($version, self::MIN_COMPATIBLE_VERSION)];
        }

        return [true, null];
    }

    /**
     * 获取响应元数据
     *
     * @param array $data 原始数据
     * @return array 元数据
     */
    public static function getMetadata(array $data): array
    {
        return [
            'timestamp' => time(),
            'apiVersion' => self::API_VERSION,
            'encoding' => 'utf-8',
            'charset' => 'UTF-8'
        ];
    }
}