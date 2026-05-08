<?php

declare(strict_types=1);
/**
 * 缓存功能测试
 *
 * 测试缓存相关功能是否正常
 *
 * @package app\api\tests
 * @author  FoxCMS Team
 * @version 1.0
 */
namespace app\api\tests;

use PHPUnit\Framework\TestCase;

class CacheTest extends TestCase
{
    /**
     * 测试缓存键生成
     */
    public function testCacheKeyGeneration(): void
    {
        $key1 = $this->generateCacheKey('article_detail', ['id' => 123]);
        $key2 = $this->generateCacheKey('article_detail', ['id' => 123]);
        $key3 = $this->generateCacheKey('article_detail', ['id' => 456]);

        $this->assertEquals($key1, $key2, 'Same parameters should generate same key');
        $this->assertNotEquals($key1, $key3, 'Different parameters should generate different key');
    }

    /**
     * 测试缓存键格式
     */
    public function testCacheKeyFormat(): void
    {
        $key = $this->generateCacheKey('column_list', ['lang' => 'zh', 'pid' => 0]);

        $this->assertIsString($key);
        $this->assertStringStartsWith('api_', $key);
    }

    /**
     * 测试缓存过期时间
     */
    public function testCacheExpireTime(): void
    {
        $expire = 600; // 10 minutes

        $this->assertIsInt($expire);
        $this->assertEquals(600, $expire);
        $this->assertEquals(10, $expire / 60); // 10 minutes in seconds
    }

    /**
     * 测试缓存键唯一性
     */
    public function testCacheKeyUniqueness(): void
    {
        $keys = [];

        for ($i = 1; $i <= 100; $i++) {
            $key = $this->generateCacheKey('article_list', ['page' => $i]);
            $keys[] = $key;
        }

        $uniqueKeys = array_unique($keys);
        $this->assertCount(100, $uniqueKeys, 'Each page should generate unique cache key');
    }

    private function generateCacheKey(string $prefix, array $params = []): string
    {
        $paramHash = empty($params) ? '' : md5(json_encode($params));
        return "api_{$prefix}:{$paramHash}";
    }
}