<?php

declare(strict_types=1);
/**
 * API 异常处理类
 *
 * 统一处理 API 模块中的所有异常，返回格式化的 JSON 错误响应
 *
 * @package app\api
 * @author  FoxCMS Team
 * @version 1.0
 */
namespace app\api;

use app\api\exception\HttpRespException;
use app\api\lib\ResponseHelper;
use app\api\status\Code;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * API 异常处理器
 *
 * 继承框架的异常处理器，重写 render 方法返回统一格式的 JSON 响应
 *
 * @extends Handle
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要报告的异常类
     *
     * 这些异常通常是业务逻辑产生的预期异常，不需要记录日志
     *
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,              // HTTP 异常（如 404、403）
        HttpResponseException::class,      // HTTP 响应异常
        ModelNotFoundException::class,     // 模型未找到异常
        DataNotFoundException::class,     // 数据未找到异常
        ValidateException::class,          // 验证异常
    ];

    /**
     * 上报异常
     *
     * 将异常信息记录到日志
     *
     * @param Throwable $exception 异常对象
     */
    public function report(Throwable $exception): void
    {
        // 调用父类方法执行默认的日志记录
        parent::report($exception);
    }

    /**
     * 渲染异常
     *
     * 将异常转换为统一格式的 JSON 响应
     *
     * @param \think\Request $request 请求对象
     * @param Throwable $e 异常对象
     * @return Response
     *
     * @example
     * ```php
     * // 返回格式化的错误响应
     * {
     *     "code": 1001,
     *     "msg": "Invalid parameter",
     *     "data": null,
     *     "time": 1234567890,
     *     "requestId": "req_xxx"
     * }
     * ```
     */
    public function render($request, Throwable $e): Response
    {
        // 处理自定义的 HTTP 响应异常
        // 这类异常包含已经准备好的响应对象
        if ($e instanceof HttpRespException) {
            return $e->getResponse();
        }

        // 处理验证异常
        // 返回参数验证失败的错误
        if ($e instanceof ValidateException) {
            return ResponseHelper::invalidParam($e->getMessage());
        }

        // 处理模型未找到异常
        // 通常是 find() 方法查询不到数据
        if ($e instanceof ModelNotFoundException) {
            return ResponseHelper::notFound('Data model not found');
        }

        // 处理数据未找到异常
        // 通常是查询结果为空
        if ($e instanceof DataNotFoundException) {
            return ResponseHelper::notFound('Data not found');
        }

        // 处理 HTTP 异常
        // 如 404、403 等
        if ($e instanceof HttpException) {
            return ResponseHelper::error($e->getMessage(), $e->getStatusCode());
        }

        // 获取调试模式配置
        // 调试模式下返回详细错误信息，生产环境返回友好提示
        $debug = config('app.app_debug', false);
        $message = $debug ? $e->getMessage() : 'Internal server error';

        // 记录异常日志
        $this->logException($e);

        // 返回服务器内部错误响应
        return ResponseHelper::serverError($message);
    }

    /**
     * 记录异常日志
     *
     * 将异常详细信息记录到日志系统，便于问题排查
     *
     * @param Throwable $e 异常对象
     * @return void
     *
     * @internal
     */
    protected function logException(Throwable $e): void
    {
        // 构建日志数据
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),           // 异常发生时间
            'message' => $e->getMessage(),                 // 错误消息
            'code' => $e->getCode(),                      // 错误码
            'file' => $e->getFile(),                       // 错误文件
            'line' => $e->getLine(),                       // 错误行号
            'trace' => $e->getTraceAsString(),            // 堆栈跟踪
            'url' => request()->url(true),                // 请求 URL
            'ip' => request()->ip(),                      // 客户端 IP
            'method' => request()->method(),              // 请求方法
        ];

        // 将日志写入 error 通道
        trace($logData, 'error');
    }

    /**
     * 获取异常的可忽略性
     *
     * 判断异常是否应该被忽略（不记录日志）
     *
     * @param Throwable $e 异常对象
     * @return bool
     *
     * @internal
     */
    protected function isIgnorable(Throwable $e): bool
    {
        foreach ($this->ignoreReport as $class) {
            if ($e instanceof $class) {
                return true;
            }
        }
        return false;
    }
}