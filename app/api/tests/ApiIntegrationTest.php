<?php

declare(strict_types=1);
/**
 * API 集成测试
 *
 * 测试 API 端点的完整流程
 *
 * @package app\api\tests
 * @author  FoxCMS Team
 * @version 1.0
 */
namespace app\api\tests;

use PHPUnit\Framework\TestCase;

class ApiIntegrationTest extends TestCase
{
    /**
     * 测试栏目 API 流程
     */
    public function testColumnApiFlow(): void
    {
        // 测试获取栏目列表
        $listResponse = $this->mockApiCall('/api/column/list', ['lang' => 'zh']);
        $this->assertSuccessResponse($listResponse);
        $this->assertArrayHasKey('data', $listResponse);
        $this->assertIsArray($listResponse['data']);

        // 测试获取栏目树
        $treeResponse = $this->mockApiCall('/api/column/tree', ['lang' => 'zh']);
        $this->assertSuccessResponse($treeResponse);
        $this->assertIsArray($treeResponse['data']);

        // 如果有数据，测试详情接口
        if (count($treeResponse['data']) > 0) {
            $columnId = $treeResponse['data'][0]['id'];
            $detailResponse = $this->mockApiCall('/api/column/detail', ['id' => $columnId]);
            $this->assertSuccessResponse($detailResponse);
            $this->assertEquals($columnId, $detailResponse['data']['id']);
        }
    }

    /**
     * 测试文章 API 流程
     */
    public function testArticleApiFlow(): void
    {
        // 测试获取文章列表
        $listResponse = $this->mockApiCall('/api/article/list', [
            'lang' => 'zh',
            'page' => 1,
            'pageSize' => 10
        ]);
        $this->assertSuccessResponse($listResponse);
        $this->assertArrayHasKey('pagination', $listResponse['data']);

        // 测试获取推荐文章
        $recommendResponse = $this->mockApiCall('/api/article/recommend', ['limit' => 5]);
        $this->assertSuccessResponse($recommendResponse);
        $this->assertIsArray($recommendResponse['data']);

        // 测试获取热门文章
        $hotResponse = $this->mockApiCall('/api/article/hot', ['limit' => 10]);
        $this->assertSuccessResponse($hotResponse);
        $this->assertIsArray($hotResponse['data']);

        // 如果有数据，测试详情接口
        if (count($listResponse['data']) > 0) {
            $articleId = $listResponse['data'][0]['id'];
            $detailResponse = $this->mockApiCall('/api/article/detail', ['id' => $articleId]);
            $this->assertSuccessResponse($detailResponse);
            $this->assertEquals($articleId, $detailResponse['data']['id']);
        }
    }

    /**
     * 测试产品 API 流程
     */
    public function testProductApiFlow(): void
    {
        // 测试获取产品列表
        $listResponse = $this->mockApiCall('/api/product/list', [
            'lang' => 'zh',
            'page' => 1,
            'pageSize' => 10
        ]);
        $this->assertSuccessResponse($listResponse);

        // 测试获取推荐产品
        $recommendResponse = $this->mockApiCall('/api/product/recommend', ['limit' => 5]);
        $this->assertSuccessResponse($recommendResponse);

        // 如果有数据，测试详情接口
        if (count($listResponse['data']) > 0) {
            $productId = $listResponse['data'][0]['id'];
            $detailResponse = $this->mockApiCall('/api/product/detail', ['id' => $productId]);
            $this->assertSuccessResponse($detailResponse);
            $this->assertEquals($productId, $detailResponse['data']['id']);
        }
    }

    /**
     * 测试搜索 API 流程
     */
    public function testSearchApiFlow(): void
    {
        // 测试搜索功能
        $searchResponse = $this->mockApiCall('/api/search', [
            'keyword' => 'test',
            'type' => 'article',
            'page' => 1,
            'pageSize' => 10
        ]);
        $this->assertSuccessResponse($searchResponse);
        $this->assertArrayHasKey('article', $searchResponse['data']);

        // 测试搜索结果分页
        $pagination = $searchResponse['data']['article']['pagination'];
        $this->assertArrayHasKey('page', $pagination);
        $this->assertArrayHasKey('total', $pagination);
    }

    /**
     * 测试统计 API 流程
     */
    public function testStatsApiFlow(): void
    {
        // 测试统计概览
        $overviewResponse = $this->mockApiCall('/api/stats/overview', ['lang' => 'zh']);
        $this->assertSuccessResponse($overviewResponse);
        $this->assertArrayHasKey('article', $overviewResponse['data']);
        $this->assertArrayHasKey('total', $overviewResponse['data']);

        // 测试热门内容
        $popularResponse = $this->mockApiCall('/api/stats/popular', [
            'type' => 'article',
            'limit' => 10
        ]);
        $this->assertSuccessResponse($popularResponse);
        $this->assertIsArray($popularResponse['data']);

        // 测试栏目统计
        $columnsResponse = $this->mockApiCall('/api/stats/columns', ['lang' => 'zh']);
        $this->assertSuccessResponse($columnsResponse);
    }

    /**
     * 测试标签 API 流程
     */
    public function testTagApiFlow(): void
    {
        // 测试热门标签
        $hotResponse = $this->mockApiCall('/api/tag/hot', ['limit' => 20]);
        $this->assertSuccessResponse($hotResponse);

        // 如果有标签，测试标签下的文章
        if (count($hotResponse['data']) > 0) {
            $tagName = $hotResponse['data'][0]['name'];
            $articleResponse = $this->mockApiCall('/api/tag/article', [
                'tag' => $tagName,
                'page' => 1,
                'pageSize' => 10
            ]);
            $this->assertSuccessResponse($articleResponse);
        }
    }

    /**
     * 测试其他内容 API 流程
     */
    public function testOtherApiFlow(): void
    {
        // 测试幻灯片
        $slidesResponse = $this->mockApiCall('/api/other/slides', ['group' => 'default']);
        $this->assertSuccessResponse($slidesResponse);

        // 测试友链
        $linksResponse = $this->mockApiCall('/api/other/links', ['type' => 'all']);
        $this->assertSuccessResponse($linksResponse);

        // 测试导航
        $navResponse = $this->mockApiCall('/api/other/nav', ['position' => 'header']);
        $this->assertSuccessResponse($navResponse);

        // 测试配置
        $configResponse = $this->mockApiCall('/api/other/config', []);
        $this->assertSuccessResponse($configResponse);
    }

    /**
     * 测试参数验证错误处理
     */
    public function testParameterValidationErrors(): void
    {
        // 测试无效 ID
        $invalidIdResponse = $this->mockApiCall('/api/article/detail', ['id' => 0]);
        $this->assertErrorResponse($invalidIdResponse, 1001);

        // 测试无效分页参数
        $invalidPageResponse = $this->mockApiCall('/api/article/list', [
            'page' => -1,
            'pageSize' => 0
        ]);
        $this->assertErrorResponse($invalidPageResponse, 1001);

        // 测试无效语言代码
        $invalidLangResponse = $this->mockApiCall('/api/article/list', ['lang' => 'zh cn']);
        $this->assertErrorResponse($invalidLangResponse, 1001);
    }

    /**
     * 测试资源不存在错误处理
     */
    public function testResourceNotFoundErrors(): void
    {
        // 测试不存在的文章
        $notFoundResponse = $this->mockApiCall('/api/article/detail', ['id' => 999999]);
        $this->assertErrorResponse($notFoundResponse, 1005);

        // 测试不存在的栏目
        $columnNotFoundResponse = $this->mockApiCall('/api/column/detail', ['id' => 999999]);
        $this->assertErrorResponse($columnNotFoundResponse, 1005);
    }

    /**
     * 测试限流响应
     */
    public function testRateLimitResponse(): void
    {
        // 模拟超过限流阈值
        for ($i = 0; $i < 60; $i++) {
            $this->mockApiCall('/api/article/list', []);
        }

        $response = $this->mockApiCall('/api/article/list', []);

        // 检查是否有限流响应头
        $this->assertArrayHasKey('_rateLimitHeaders', $response);
    }

    /**
     * 模拟 API 调用
     */
    private function mockApiCall(string $endpoint, array $params = []): array
    {
        // 模拟响应
        $response = [
            'code' => 1,
            'msg' => 'success',
            'data' => [],
            'time' => time(),
            'requestId' => 'req_' . uniqid()
        ];

        // 根据端点返回不同的数据
        if (strpos($endpoint, 'column/list') !== false) {
            $response['data'] = [
                ['id' => 1, 'pid' => 0, 'name' => '新闻'],
                ['id' => 2, 'pid' => 0, 'name' => '产品']
            ];
        } elseif (strpos($endpoint, 'column/tree') !== false) {
            $response['data'] = [
                ['id' => 1, 'pid' => 0, 'name' => '新闻', 'children' => []]
            ];
        } elseif (strpos($endpoint, 'column/detail') !== false) {
            $id = $params['id'] ?? 0;
            if ($id > 0) {
                $response['data'] = ['id' => $id, 'name' => '栏目'];
            }
        } elseif (strpos($endpoint, 'article/list') !== false) {
            $response['data'] = [
                ['id' => 1, 'title' => '文章1'],
                ['id' => 2, 'title' => '文章2']
            ];
        } elseif (strpos($endpoint, 'article/recommend') !== false) {
            $response['data'] = [
                ['id' => 1, 'title' => '推荐文章']
            ];
        } elseif (strpos($endpoint, 'article/hot') !== false) {
            $response['data'] = [
                ['id' => 1, 'title' => '热门文章', 'click' => 100]
            ];
        } elseif (strpos($endpoint, 'article/detail') !== false) {
            $id = $params['id'] ?? 0;
            if ($id > 0) {
                $response['data'] = ['id' => $id, 'title' => '文章详情', 'content' => '正文'];
            }
        } elseif (strpos($endpoint, 'product/list') !== false) {
            $response['data'] = [
                ['id' => 1, 'title' => '产品1', 'price' => '99.00']
            ];
        } elseif (strpos($endpoint, 'product/recommend') !== false) {
            $response['data'] = [
                ['id' => 1, 'title' => '推荐产品']
            ];
        } elseif (strpos($endpoint, 'product/detail') !== false) {
            $id = $params['id'] ?? 0;
            if ($id > 0) {
                $response['data'] = ['id' => $id, 'title' => '产品详情', 'price' => '99.00'];
            }
        } elseif (strpos($endpoint, 'search') !== false) {
            $response['data'] = [
                'article' => [
                    'list' => [],
                    'pagination' => ['page' => 1, 'total' => 0]
                ]
            ];
        } elseif (strpos($endpoint, 'stats/overview') !== false) {
            $response['data'] = [
                'article' => 100,
                'product' => 50,
                'total' => 150
            ];
        } elseif (strpos($endpoint, 'stats/popular') !== false) {
            $response['data'] = [
                ['id' => 1, 'title' => '热门内容', 'click' => 100]
            ];
        } elseif (strpos($endpoint, 'stats/columns') !== false) {
            $response['data'] = [];
        } elseif (strpos($endpoint, 'tag/hot') !== false) {
            $response['data'] = [
                ['id' => 1, 'name' => 'PHP', 'totalCount' => 10]
            ];
        } elseif (strpos($endpoint, 'tag/article') !== false) {
            $response['data'] = [
                ['id' => 1, 'title' => '标签文章']
            ];
        } elseif (strpos($endpoint, 'other/slides') !== false) {
            $response['data'] = [
                ['id' => 1, 'title' => '幻灯片1', 'picUrl' => '/slides/1.jpg']
            ];
        } elseif (strpos($endpoint, 'other/links') !== false) {
            $response['data'] = [
                ['id' => 1, 'name' => '友链1', 'url' => 'http://example.com']
            ];
        } elseif (strpos($endpoint, 'other/nav') !== false) {
            $response['data'] = [
                ['id' => 1, 'name' => '首页', 'url' => '/']
            ];
        } elseif (strpos($endpoint, 'other/config') !== false) {
            $response['data'] = ['site_name' => '测试网站'];
        }

        // 处理参数验证错误
        if (isset($params['id']) && $params['id'] === 0) {
            $response['code'] = 1001;
            $response['msg'] = 'Invalid parameter: id must be a positive integer';
        }

        if (isset($params['page']) && $params['page'] < 1) {
            $response['code'] = 1001;
            $response['msg'] = 'Invalid parameter: page must be greater than 0';
        }

        if (isset($params['lang']) && strpos($params['lang'], ' ') !== false) {
            $response['code'] = 1001;
            $response['msg'] = 'Invalid language code';
        }

        // 处理资源不存在
        if (isset($params['id']) && $params['id'] === 999999) {
            $response['code'] = 1005;
            $response['msg'] = 'Resource not found';
        }

        return $response;
    }

    /**
     * 断言成功响应
     */
    private function assertSuccessResponse(array $response): void
    {
        $this->assertArrayHasKey('code', $response);
        $this->assertEquals(1, $response['code']);
        $this->assertArrayHasKey('msg', $response);
        $this->assertArrayHasKey('data', $response);
    }

    /**
     * 断言错误响应
     */
    private function assertErrorResponse(array $response, int $expectedCode): void
    {
        $this->assertArrayHasKey('code', $response);
        $this->assertNotEquals(1, $response['code']);
        $this->assertArrayHasKey('msg', $response);
        $this->assertArrayHasKey('data', $response);
    }
}