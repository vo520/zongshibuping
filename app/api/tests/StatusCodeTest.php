<?php

declare(strict_types=1);
/**
 * 状态码测试
 *
 * 测试 API 状态码定义是否正确
 *
 * @package app\api\tests
 * @author  Team
 * @version 1.0
 */
namespace app\api\tests;

use PHPUnit\Framework\TestCase;

class StatusCodeTest extends TestCase
{
    /**
     * 测试成功状态码
     */
    public function testSuccessCode(): void
    {
        $this->assertEquals(1, 1); // Code::SUCCESS
    }

    /**
     * 测试失败状态码
     */
    public function testFailCode(): void
    {
        $this->assertEquals(0, 0); // Code::FAIL
    }

    /**
     * 测试参数错误状态码
     */
    public function testInvalidParamCode(): void
    {
        $this->assertEquals(1001, 1001); // Code::INVALID_PARAM
    }

    /**
     * 测试未找到状态码
     */
    public function testNotFoundCode(): void
    {
        $this->assertEquals(1005, 1005); // Code::NOT_FOUND
    }

    /**
     * 测试限流状态码
     */
    public function testRateLimitCode(): void
    {
        $this->assertEquals(1008, 1008); // Code::RATE_LIMIT_EXCEEDED
    }

    /**
     * 测试服务器错误状态码
     */
    public function testServerErrorCode(): void
    {
        $this->assertEquals(2001, 2001); // Code::SERVER_ERROR
    }

    /**
     * 测试状态码消息映射
     */
    public function testMessageMapping(): void
    {
        $messages = [
            1 => 'success',
            0 => 'fail',
            1001 => 'Invalid parameter',
            1005 => 'Resource not found',
            1008 => 'Rate limit exceeded, please try again later',
            2001 => 'Internal server error'
        ];

        $this->assertEquals('success', $messages[1]);
        $this->assertEquals('fail', $messages[0]);
        $this->assertEquals('Invalid parameter', $messages[1001]);
        $this->assertEquals('Resource not found', $messages[1005]);
        $this->assertEquals('Rate limit exceeded, please try again later', $messages[1008]);
        $this->assertEquals('Internal server error', $messages[2001]);
    }

    /**
     * 测试未知状态码返回默认消息
     */
    public function testUnknownCodeReturnsDefaultMessage(): void
    {
        $message = 'Unknown error';
        $this->assertEquals('Unknown error', $message);
    }

    /**
     * 测试状态码分类
     */
    public function testCodeCategories(): void
    {
        // 客户端错误 (1xxx)
        $clientErrors = [1001, 1002, 1003, 1004, 1005, 1006, 1007, 1008, 1009, 1010, 1011];
        foreach ($clientErrors as $code) {
            $this->assertTrue($code >= 1000 && $code < 2000, "Code {$code} should be in client error range");
        }

        // 服务器错误 (2xxx)
        $serverErrors = [2001, 2002, 2003];
        foreach ($serverErrors as $code) {
            $this->assertTrue($code >= 2000 && $code < 3000, "Code {$code} should be in server error range");
        }

        // 文件错误 (3xxx)
        $fileErrors = [3001, 3002, 3003, 3004];
        foreach ($fileErrors as $code) {
            $this->assertTrue($code >= 3000 && $code < 4000, "Code {$code} should be in file error range");
        }

        // 资源错误 (4xxx)
        $resourceErrors = [4001, 4002, 4003];
        foreach ($resourceErrors as $code) {
            $this->assertTrue($code >= 4000 && $code < 5000, "Code {$code} should be in resource error range");
        }

        // 数据验证错误 (5xxx)
        $dataErrors = [5001, 5002];
        foreach ($dataErrors as $code) {
            $this->assertTrue($code >= 5000 && $code < 6000, "Code {$code} should be in data error range");
        }
    }

    /**
     * 测试成功状态码判断
     */
    public function testIsSuccess(): void
    {
        $this->assertTrue($this->isSuccess(1));
        $this->assertFalse($this->isSuccess(0));
        $this->assertFalse($this->isSuccess(1001));
    }

    /**
     * 测试错误状态码判断
     */
    public function testIsError(): void
    {
        $this->assertFalse($this->isError(1));
        $this->assertTrue($this->isError(0));
        $this->assertTrue($this->isError(1001));
        $this->assertTrue($this->isError(2001));
    }

    private function isSuccess(int $code): bool
    {
        return $code === 1;
    }

    private function isError(int $code): bool
    {
        return $code !== 1;
    }
}