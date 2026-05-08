<?php

declare(strict_types=1);
/**
 * API 自定义异常类
 *
 * 用于在控制器中抛出需要返回特定响应的异常
 *
 * @package app\api\exception
 * @author  FoxCMS Team
 * @version 1.0
 */
namespace app\api\exception;

use think\Response;

/**
 * HTTP 响应异常
 *
 * 抛出此异常可以直接指定要返回的响应对象
 * 常用于需要中断执行并返回特定响应的场景
 *
 * @example
 * ```php
 * throw new HttpRespException(ResponseHelper::notFound('Resource not found'));
 * ```
 */
class HttpRespException extends \Exception
{
    /**
     * 响应对象
     *
     * @var Response
     */
    protected $response;

    /**
     * 构造函数
     *
     * @param Response $response 要返回的响应对象
     * @param string $message 错误消息
     * @param int $code 错误码
     */
    public function __construct(Response $response, string $message = '', int $code = 0)
    {
        parent::__construct($message, $code);
        $this->response = $response;
    }

    /**
     * 获取响应对象
     *
     * @return Response
     */
    public function getResponse(): Response
    {
        return $this->response;
    }
}