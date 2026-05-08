<?php

declare(strict_types=1);
/**
 * 响应辅助类
 *
 * 提供统一的 JSON 响应格式封装，包括：
 * - 成功响应
 * - 错误响应
 * - 分页响应
 * - 常用错误响应快捷方法
 *
 * @package app\api\lib
 * @author  FoxCMS Team
 * @version 1.0
 */
namespace app\api\lib;

use app\api\status\Code;
use think\Response;

/**
 * 响应辅助类
 *
 * 统一 API 响应格式，确保所有接口返回一致的 JSON 结构
 *
 * @example
 * ```php
 * // 成功响应
 * return ResponseHelper::success(['id' => 1], '操作成功');
 *
 * // 错误响应
 * return ResponseHelper::error('操作失败', Code::INVALID_PARAM);
 *
 * // 分页响应
 * return ResponseHelper::paginate($list, $page, $pageSize, $total);
 * ```
 */
class ResponseHelper
{
    /**
     * 生成成功响应
     *
     * 返回统一格式的成功 JSON 响应
     *
     * @param mixed $data 响应数据
     * @param string|null $msg 成功消息，默认使用状态码对应的消息
     * @param int|null $code 状态码，默认 1 表示成功
     * @return Response JSON 格式的响应对象
     *
     * @example
     * ```php
     * // 基本用法
     * return ResponseHelper::success();
     *
     * // 带数据
     * return ResponseHelper::success(['id' => 1, 'name' => 'test']);
     *
     * // 带消息
     * return ResponseHelper::success($data, '数据获取成功');
     *
     * // 自定义状态码
     * return ResponseHelper::success($data, '操作完成', 200);
     * ```
     */
    public static function success($data = null, string $msg = null, int $code = null): Response
    {
        // 使用默认值
        $code = $code ?? Code::SUCCESS;
        $msg = $msg ?? Code::getMessage($code);

        // 构建响应数组
        $result = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'time' => time(),
            'requestId' => self::getRequestId()
        ];

        return json($result);
    }

    /**
     * 生成错误响应
     *
     * 返回统一格式的错误 JSON 响应
     *
     * @param string|null $msg 错误消息，默认使用状态码对应的消息
     * @param int|null $code 错误码，默认 0 表示失败
     * @param mixed $data 附加数据，可选
     * @return Response JSON 格式的响应对象
     *
     * @example
     * ```php
     * // 基本用法
     * return ResponseHelper::error();
     *
     * // 带消息
     * return ResponseHelper::error('参数错误');
     *
     * // 带错误码
     * return ResponseHelper::error('未授权访问', Code::UNAUTHORIZED);
     *
     * // 带附加数据
     * return ResponseHelper::error('验证失败', Code::INVALID_PARAM, ['field' => 'email']);
     * ```
     */
    public static function error(string $msg = null, int $code = null, $data = null): Response
    {
        // 使用默认值
        $code = $code ?? Code::FAIL;
        $msg = $msg ?? Code::getMessage($code);

        // 构建响应数组
        $result = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'time' => time(),
            'requestId' => self::getRequestId()
        ];

        return json($result);
    }

    /**
     * 生成分页响应
     *
     * 将列表数据和分页信息组合成统一格式
     *
     * @param array $list 数据列表
     * @param int $page 当前页码
     * @param int $pageSize 每页数量
     * @param int $total 总记录数
     * @return Response JSON 格式的分页响应
     *
     * @example
     * ```php
     * $result = ResponseHelper::paginate($articles, 1, 15, 100);
     * ```
     */
    public static function paginate(array $list, int $page, int $pageSize, int $total): Response
    {
        // 计算总页数
        $totalPages = $total > 0 ? ceil($total / $pageSize) : 0;

        return self::success([
            'list' => $list,
            'pagination' => [
                'page' => (int)$page,
                'pageSize' => (int)$pageSize,
                'total' => (int)$total,
                'totalPages' => (int)$totalPages,
                'hasMore' => $page * $pageSize < $total
            ]
        ]);
    }

    /**
     * 资源未找到响应
     *
     * 返回 404 类型的错误响应
     *
     * @param string $msg 自定义错误消息，默认 'Resource not found'
     * @return Response JSON 格式的 404 响应
     *
     * @example
     * ```php
     * return ResponseHelper::notFound('Article not found');
     * ```
     */
    public static function notFound(string $msg = 'Resource not found'): Response
    {
        return self::error($msg, Code::NOT_FOUND);
    }

    /**
     * 未授权响应
     *
     * 返回 401 类型的错误响应
     *
     * @param string $msg 自定义错误消息，默认 'Unauthorized'
     * @return Response JSON 格式的 401 响应
     *
     * @example
     * ```php
     * return ResponseHelper::unauthorized('请先登录');
     * ```
     */
    public static function unauthorized(string $msg = 'Unauthorized'): Response
    {
        return self::error($msg, Code::UNAUTHORIZED);
    }

    /**
     * 禁止访问响应
     *
     * 返回 403 类型的错误响应
     *
     * @param string $msg 自定义错误消息，默认 'Access forbidden'
     * @return Response JSON 格式的 403 响应
     *
     * @example
     * ```php
     * return ResponseHelper::forbidden('您没有权限访问此资源');
     * ```
     */
    public static function forbidden(string $msg = 'Access forbidden'): Response
    {
        return self::error($msg, Code::FORBIDDEN);
    }

    /**
     * 参数错误响应
     *
     * 返回参数验证失败的错误响应
     *
     * @param string $msg 自定义错误消息，默认 'Invalid parameter'
     * @return Response JSON 格式的参数错误响应
     *
     * @example
     * ```php
     * return ResponseHelper::invalidParam('ID must be a positive integer');
     * ```
     */
    public static function invalidParam(string $msg = 'Invalid parameter'): Response
    {
        return self::error($msg, Code::INVALID_PARAM);
    }

    /**
     * 缺少必需参数响应
     *
     * @param string $msg 自定义错误消息
     * @return Response JSON 格式的缺少参数响应
     *
     * @example
     * ```php
     * return ResponseHelper::missingParam('Missing required parameter: id');
     * ```
     */
    public static function missingParam(string $msg = 'Missing required parameter'): Response
    {
        return self::error($msg, Code::MISSING_PARAM);
    }

    /**
     * 限流响应
     *
     * 返回请求频率超限的错误响应
     *
     * @param string $msg 自定义错误消息
     * @return Response JSON 格式的限流响应
     *
     * @example
     * ```php
     * return ResponseHelper::rateLimit('Too many requests, please try again later');
     * ```
     */
    public static function rateLimit(string $msg = 'Rate limit exceeded'): Response
    {
        return self::error($msg, Code::RATE_LIMIT_EXCEEDED);
    }

    /**
     * 服务器内部错误响应
     *
     * 返回 500 类型的错误响应
     *
     * @param string $msg 自定义错误消息
     * @return Response JSON 格式的 500 响应
     *
     * @example
     * ```php
     * return ResponseHelper::serverError('An unexpected error occurred');
     * ```
     */
    public static function serverError(string $msg = 'Internal server error'): Response
    {
        return self::error($msg, Code::SERVER_ERROR);
    }

    /**
     * 无效签名响应
     *
     * @param string $msg 自定义错误消息
     * @return Response JSON 格式的签名错误响应
     *
     * @example
     * ```php
     * return ResponseHelper::invalidSignature('Signature verification failed');
     * ```
     */
    public static function invalidSignature(string $msg = 'Invalid signature'): Response
    {
        return self::error($msg, Code::INVALID_SIGNATURE);
    }

    /**
     * Token 过期响应
     *
     * @param string $msg 自定义错误消息
     * @return Response JSON 格式的 Token 过期响应
     *
     * @example
     * ```php
     * return ResponseHelper::tokenExpired('Token has expired, please login again');
     * ```
     */
    public static function tokenExpired(string $msg = 'Token expired'): Response
    {
        return self::error($msg, Code::TOKEN_EXPIRED);
    }

    /**
     * 无效 Token 响应
     *
     * @param string $msg 自定义错误消息
     * @return Response JSON 格式的无效 Token 响应
     *
     * @example
     * ```php
     * return ResponseHelper::tokenInvalid('Invalid token');
     * ```
     */
    public static function tokenInvalid(string $msg = 'Invalid token'): Response
    {
        return self::error($msg, Code::TOKEN_INVALID);
    }

    /**
     * 获取请求 ID
     *
     * 从请求头中获取或生成唯一的请求 ID，用于追踪日志
     *
     * @return string 请求 ID
     *
     * @internal
     */
    private static function getRequestId(): string
    {
        // 优先从请求头获取
        if (isset($_SERVER['HTTP_X_REQUEST_ID']) && !empty($_SERVER['HTTP_X_REQUEST_ID'])) {
            return $_SERVER['HTTP_X_REQUEST_ID'];
        }

        // 生成唯一 ID
        return 'req_' . uniqid() . '_' . mt_rand(1000, 9999);
    }
}