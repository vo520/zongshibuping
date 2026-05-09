<?php

declare(strict_types=1);
/**
 * API 状态码定义
 *
 * 定义了系统中使用的所有状态码及其对应的默认消息
 *
 * 状态码规范：
 * - 1xxx: 客户端参数错误
 * - 2xxx: 服务器错误
 * - 3xxx: 文件相关错误
 * - 4xxx: 资源相关错误
 * - 5xxx: 数据验证错误
 *
 * @package app\api\status
 * @author  Team
 * @version 1.0
 */
namespace app\api\status;

/**
 * API 状态码类
 *
 * 提供标准化的状态码定义和消息获取
 */
class Code
{
    // ==================== 通用状态码 ====================

    /** 成功 */
    public const SUCCESS = 1;

    /** 失败 */
    public const FAIL = 0;

    // ==================== 客户端错误 (1xxx) ====================

    /** 参数错误 - 传入的参数格式不正确或值不符合要求 */
    public const INVALID_PARAM = 1001;

    /** 缺少必需参数 - 必需的参数未传入 */
    public const MISSING_PARAM = 1002;

    /** 未认证 - 需要登录才能访问 */
    public const UNAUTHORIZED = 1003;

    /** 禁止访问 - 已认证但权限不足 */
    public const FORBIDDEN = 1004;

    /** 资源未找到 - 请求的资源不存在 */
    public const NOT_FOUND = 1005;

    /** 方法不允许 - HTTP 方法不被允许 */
    public const METHOD_NOT_ALLOWED = 1006;

    /** 请求超时 */
    public const REQUEST_TIMEOUT = 1007;

    /** 请求频率超限 - 超过 API 调用限制 */
    public const RATE_LIMIT_EXCEEDED = 1008;

    /** 无效签名 - 请求签名验证失败 */
    public const INVALID_SIGNATURE = 1009;

    /** Token 过期 */
    public const TOKEN_EXPIRED = 1010;

    /** 无效 Token */
    public const TOKEN_INVALID = 1011;

    // ==================== 服务器错误 (2xxx) ====================

    /** 服务器内部错误 - 代码执行过程中发生未预期的错误 */
    public const SERVER_ERROR = 2001;

    /** 数据库错误 - 数据库操作失败 */
    public const DATABASE_ERROR = 2002;

    /** 缓存错误 - 缓存操作失败 */
    public const CACHE_ERROR = 2003;

    // ==================== 文件相关错误 (3xxx) ====================

    /** 文件未找到 */
    public const FILE_NOT_FOUND = 3001;

    /** 文件上传错误 */
    public const FILE_UPLOAD_ERROR = 3002;

    /** 文件大小超限 */
    public const FILE_SIZE_EXCEEDED = 3003;

    /** 无效的文件类型 */
    public const INVALID_FILE_TYPE = 3004;

    // ==================== 资源相关错误 (4xxx) ====================

    /** 资源未找到 */
    public const RESOURCE_NOT_FOUND = 4001;

    /** 资源已禁用 */
    public const RESOURCE_DISABLED = 4002;

    /** 资源已删除 */
    public const RESOURCE_DELETED = 4003;

    // ==================== 数据验证错误 (5xxx) ====================

    /** 数据验证错误 */
    public const DATA_VALIDATION_ERROR = 5001;

    /** 重复条目 - 数据已存在 */
    public const DUPLICATE_ENTRY = 5002;

    /**
     * 状态码对应的默认消息
     *
     * @var array
     */
    public static $messages = [
        // 通用状态码
        self::SUCCESS => 'success',
        self::FAIL => 'fail',

        // 客户端错误
        self::INVALID_PARAM => 'Invalid parameter',
        self::MISSING_PARAM => 'Missing required parameter',
        self::UNAUTHORIZED => 'Unauthorized',
        self::FORBIDDEN => 'Access forbidden',
        self::NOT_FOUND => 'Resource not found',
        self::METHOD_NOT_ALLOWED => 'Method not allowed',
        self::REQUEST_TIMEOUT => 'Request timeout',
        self::RATE_LIMIT_EXCEEDED => 'Rate limit exceeded, please try again later',
        self::INVALID_SIGNATURE => 'Invalid signature',
        self::TOKEN_EXPIRED => 'Token expired',
        self::TOKEN_INVALID => 'Invalid token',

        // 服务器错误
        self::SERVER_ERROR => 'Internal server error',
        self::DATABASE_ERROR => 'Database error',
        self::CACHE_ERROR => 'Cache error',

        // 文件相关错误
        self::FILE_NOT_FOUND => 'File not found',
        self::FILE_UPLOAD_ERROR => 'File upload error',
        self::FILE_SIZE_EXCEEDED => 'File size exceeded',
        self::INVALID_FILE_TYPE => 'Invalid file type',

        // 资源相关错误
        self::RESOURCE_NOT_FOUND => 'Resource not found',
        self::RESOURCE_DISABLED => 'Resource is disabled',
        self::RESOURCE_DELETED => 'Resource has been deleted',

        // 数据验证错误
        self::DATA_VALIDATION_ERROR => 'Data validation error',
        self::DUPLICATE_ENTRY => 'Duplicate entry',
    ];

    /**
     * 获取状态码对应的消息
     *
     * @param int $code 状态码
     * @return string 对应的消息，如果状态码不存在返回 'Unknown error'
     *
     * @example
     * ```php
     * $message = Code::getMessage(Code::SUCCESS);       // 返回 'success'
     * $message = Code::getMessage(Code::NOT_FOUND);      // 返回 'Resource not found'
     * $message = Code::getMessage(99999);               // 返回 'Unknown error'
     * ```
     */
    public static function getMessage(int $code): string
    {
        // 如果存在对应的消息，返回该消息
        if (isset(self::$messages[$code])) {
            return self::$messages[$code];
        }

        // 否则返回默认的未知错误消息
        return 'Unknown error';
    }

    /**
     * 检查是否为成功状态码
     *
     * @param int $code 状态码
     * @return bool
     *
     * @example
     * ```php
     * if (Code::isSuccess($responseCode)) {
     *     // 处理成功情况
     * }
     * ```
     */
    public static function isSuccess(int $code): bool
    {
        return $code === self::SUCCESS;
    }

    /**
     * 检查是否为错误状态码
     *
     * @param int $code 状态码
     * @return bool
     *
     * @example
     * ```php
     * if (Code::isError($responseCode)) {
     *     // 处理错误情况
     * }
     * ```
     */
    public static function isError(int $code): bool
    {
        return $code !== self::SUCCESS;
    }

    /**
     * 获取所有状态码列表
     *
     * @return array 状态码及其消息的映射
     *
     * @example
     * ```php
     * $codes = Code::getAllCodes();
     * foreach ($codes as $code => $message) {
     *     echo "{$code}: {$message}";
     * }
     * ```
     */
    public static function getAllCodes(): array
    {
        return self::$messages;
    }
}