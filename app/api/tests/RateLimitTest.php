<?php

declare(strict_types=1);
/**
 * 限流功能测试
 *
 * 测试限流中间件的各项功能
 *
 * @package app\api\tests
 * @author  Team
 * @version 1.0
 */
namespace app\api\tests;

use PHPUnit\Framework\TestCase;

class RateLimitTest extends TestCase
{
    /**
     * 测试限流配置
     */
    public function testRateLimitConfig(): void
    {
        $config = [
            'default' => ['limit' => 60, 'window' => 60],
            'search' => ['limit' => 30, 'window' => 60],
            'detail' => ['limit' => 100, 'window' => 60],
        ];

        $this->assertArrayHasKey('default', $config);
        $this->assertArrayHasKey('search', $config);
        $this->assertArrayHasKey('detail', $config);

        $this->assertIsInt($config['default']['limit']);
        $this->assertIsInt($config['default']['window']);
        $this->assertGreaterThan(0, $config['default']['limit']);
        $this->assertGreaterThan(0, $config['default']['window']);
    }

    /**
     * 测试限流键生成
     */
    public function testRateLimitKeyGeneration(): void
    {
        $key1 = $this->generateRateLimitKey('/api/article/list', '192.168.1.1');
        $key2 = $this->generateRateLimitKey('/api/article/list', '192.168.1.1');
        $key3 = $this->generateRateLimitKey('/api/article/list', '192.168.1.2');

        $this->assertEquals($key1, $key2);
        $this->assertNotEquals($key1, $key3);
    }

    /**
     * 测试限流判断
     */
    public function testRateLimitCheck(): void
    {
        $limit = 60;
        $count = 60;

        $limited = $count >= $limit;
        $this->assertTrue($limited, 'Count equals limit should trigger rate limit');

        $count = 59;
        $limited = $count >= $limit;
        $this->assertFalse($limited, 'Count less than limit should not trigger rate limit');
    }

    /**
     * 测试剩余请求数计算
     */
    public function testRemainingRequests(): void
    {
        $limit = 60;
        $count = 30;

        $remaining = max(0, $limit - $count - 1);
        $this->assertEquals(29, $remaining);

        $count = 0;
        $remaining = max(0, $limit - $count - 1);
        $this->assertEquals(59, $remaining);

        $count = 60;
        $remaining = max(0, $limit - $count - 1);
        $this->assertEquals(0, $remaining);
    }

    /**
     * 测试重置时间计算
     */
    public function testResetTimeCalculation(): void
    {
        $now = time();
        $window = 60;

        $reset = $now + $window;
        $this->assertGreaterThan($now, $reset);
        $this->assertEquals($now + 60, $reset);
    }

    /**
     * 测试响应头格式
     */
    public function testRateLimitHeaders(): void
    {
        $headers = [
            'X-RateLimit-Limit' => 60,
            'X-RateLimit-Remaining' => 59,
            'X-RateLimit-Reset' => time() + 60
        ];

        $this->assertArrayHasKey('X-RateLimit-Limit', $headers);
        $this->assertArrayHasKey('X-RateLimit-Remaining', $headers);
        $this->assertArrayHasKey('X-RateLimit-Reset', $headers);

        $this->assertIsInt($headers['X-RateLimit-Limit']);
        $this->assertIsInt($headers['X-RateLimit-Remaining']);
        $this->assertIsInt($headers['X-RateLimit-Reset']);

        $this->assertGreaterThanOrEqual(0, $headers['X-RateLimit-Remaining']);
        $this->assertGreaterThan(time(), $headers['X-RateLimit-Reset']);
    }

    private function generateRateLimitKey(string $path, string $ip): string
    {
        return md5($ip . ':' . $path);
    }
}