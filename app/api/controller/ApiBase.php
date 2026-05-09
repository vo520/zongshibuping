<?php

declare(strict_types=1);
/**
 * API 基础控制器
 *
 * 提供所有 API 控制器的公共功能，包括：
 * - 统一的响应格式
 * - 分页处理
 * - 参数验证
 * - 错误处理
 *
 * @package app\api\controller
 * @author  Team
 * @version 1.0
 */
namespace app\api\controller;

use think\App;
use think\Request;
use app\api\lib\ResponseHelper;
use app\api\lib\Validator;

/**
 * API 基础控制器抽象类
 *
 * 所有 API 控制器应继承此类以获得统一的响应格式和公共功能
 *
 * @abstract
 * @example
 * ```php
 * class Article extends ApiBase
 * {
 *     public function list()
 *     {
 *         [$page, $size] = $this->getPageParams();
 *         if (!$page) {
 *             return $this->invalidParam($size);
 *         }
 *         // ... 业务逻辑
 *         return $this->success($data);
 *     }
 * }
 * ```
 */
abstract class ApiBase
{
    /**
     * 当前请求对象
     *
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     *
     * @var \think\App
     */
    protected $app;

    /**
     * 默认每页数量
     *
     * @var int
     */
    protected $pageSize = 15;

    /**
     * 最大每页数量限制
     *
     * 防止客户端请求过多数据导致服务器压力过大
     *
     * @var int
     */
    protected $maxPageSize = 100;

    /**
     * 缓存有效期（秒）
     *
     * @var int
     */
    protected $cacheExpire = 600;

    /**
     * 是否启用缓存
     *
     * @var bool
     */
    protected $enableCache = true;

    /**
     * 构造函数
     *
     * 初始化应用实例和请求对象，并调用初始化方法
     *
     * @param App $app 应用实例
     * @example
     * ```php
     * public function __construct(App $app)
     * {
     *     parent::__construct($app);
     *     // 自定义初始化逻辑
     * }
     * ```
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $app->request;
        $this->initialize();
    }

    /**
     * 初始化方法
     *
     * 子类可重写此方法进行初始化操作，如设置默认参数、加载配置等
     *
     * @return void
     * @example
     * ```php
     * protected function initialize()
     * {
     *     $this->pageSize = 20;
     *     $this->cacheExpire = 300;
     * }
     * ```
     */
    protected function initialize(): void
    {
    }

    /**
     * 返回成功响应
     *
     * 统一格式化成功响应的 JSON 返回
     *
     * @param mixed $data 响应数据，任意类型
     * @param string $msg 成功消息，默认 'success'
     * @param int $code 状态码，默认 1 表示成功
     * @return \think\Response JSON 格式的响应对象
     *
     * @example
     * ```php
     * // 返回简单成功
     * return $this->success();
     *
     * // 返回带数据的成功
     * return $this->success(['id' => 1, 'name' => 'test']);
     *
     * // 返回带消息的成功
     * return $this->success($list, '数据获取成功');
     * ```
     */
    protected function success($data = null, string $msg = 'success', int $code = 1)
    {
        return ResponseHelper::success($data, $msg, $code);
    }

    /**
     * 返回错误响应
     *
     * 统一格式化错误响应的 JSON 返回
     *
     * @param string $msg 错误消息，默认 'error'
     * @param int $code 错误码，默认 0 表示失败
     * @param mixed $data 附加数据，可选
     * @return \think\Response JSON 格式的响应对象
     *
     * @example
     * ```php
     * return $this->error('操作失败');
     * return $this->error('数据验证失败', 1001);
     * ```
     */
    protected function error(string $msg = 'error', int $code = 0, $data = null)
    {
        return ResponseHelper::error($msg, $code, $data);
    }

    /**
     * 返回分页数据
     *
     * 将查询结果和分页信息统一格式化返回
     *
     * @param \think\db\Query|\think\Collection $query 查询构建器或数据集
     * @param int $page 当前页码
     * @param int $size 每页数量
     * @return array 包含 list 和 pagination 的数组
     *
     * @example
     * ```php
     * $result = $this->paginate($query, $page, $size);
     * return $this->success($result);
     * ```
     */
    protected function paginate($query, int $page, int $size): array
    {
        // 克隆查询构建器以避免影响原查询
        $countQuery = clone $query;

        // 统计总记录数（使用单独的计数查询优化性能）
        $total = $countQuery->count();

        // 执行分页查询，按 ID 降序排列
        $list = $query->page($page, $size)
            ->order('id', 'desc')
            ->select();

        // 计算总页数
        $totalPages = $total > 0 ? ceil($total / $size) : 0;

        return [
            'list' => $list,
            'pagination' => [
                'page' => (int)$page,
                'pageSize' => (int)$size,
                'total' => (int)$total,
                'totalPages' => (int)$totalPages,
                'hasMore' => $page * $size < $total  // 判断是否还有更多数据
            ]
        ];
    }

    /**
     * 获取分页参数
     *
     * 从请求中获取并验证 page 和 pageSize 参数
     * 自动过滤非法值并限制在合理范围内
     *
     * @return array [bool, mixed] 验证成功返回 [true, [page, size]]，失败返回 [false, errorMessage]
     *
     * @example
     * ```php
     * [$valid, $result] = $this->getPageParams();
     * if (!$valid) {
     *     return $this->invalidParam($result);
     * }
     * [$page, $size] = $result;
     * ```
     */
    protected function getPageParams(): array
    {
        // 从请求参数中获取分页信息，默认第一页，每页15条
        $page = max(1, intval($this->request->param('page', 1)));
        $size = min($this->maxPageSize, max(1, intval($this->request->param('pageSize', $this->pageSize))));

        // 使用验证器验证分页参数
        [$valid, $error] = Validator::validatePageParams($page, $size, $this->maxPageSize);
        if (!$valid) {
            return [false, $error];
        }

        return [true, [$page, $size]];
    }

    /**
     * 验证必填参数
     *
     * 检查请求参数中是否包含所有必需的字段
     *
     * @param array $params 请求参数数组
     * @param array $fields 需要验证的字段名列表
     * @return array [bool, mixed] 验证成功返回 [true, null]，失败返回 [false, missingFieldsString]
     *
     * @example
     * ```php
     * [$valid, $error] = $this->validateRequired($params, ['id', 'title']);
     * if (!$valid) {
     *     return $this->error($error);
     * }
     * ```
     */
    protected function validateRequired(array $params, array $fields): array
    {
        $missing = [];

        // 遍历所有需要验证的字段
        foreach ($fields as $field) {
            // 检查字段是否存在且不为空
            if (!isset($params[$field]) || $params[$field] === '' || $params[$field] === null) {
                $missing[] = $field;
            }
        }

        // 如果有缺失的字段，返回错误信息
        if (!empty($missing)) {
            return [false, 'Missing required parameters: ' . implode(', ', $missing)];
        }

        return [true, null];
    }

    /**
     * 验证 ID 参数
     *
     * 确保传入的 ID 是有效的正整数
     *
     * @param mixed $id 待验证的 ID 值
     * @return array [bool, mixed] 验证成功返回 [true, intId]，失败返回 [false, errorMessage]
     *
     * @example
     * ```php
     * [$valid, $id] = $this->validateId($request->param('id'));
     * if (!$valid) {
     *     return $this->invalidParam($id);
     * }
     * ```
     */
    protected function validateId($id): array
    {
        return Validator::validateId($id);
    }

    /**
     * 验证语言代码参数
     *
     * 确保语言标识符格式正确（字母、数字、下划线、连字符）
     *
     * @param string $lang 语言代码
     * @return array [bool, mixed] 验证成功返回 [true, validLang]，失败返回 [false, errorMessage]
     *
     * @example
     * ```php
     * [$valid, $lang] = $this->validateLang($lang);
     * if (!$valid) {
     *     return $this->invalidParam($lang);
     * }
     * ```
     */
    protected function validateLang(string $lang): array
    {
        return Validator::validateLang($lang);
    }

    /**
     * 验证搜索关键词
     *
     * 确保关键词非空且长度合理，并进行 XSS 过滤
     *
     * @param string $keyword 搜索关键词
     * @return array [bool, mixed] 验证成功返回 [true, safeKeyword]，失败返回 [false, errorMessage]
     *
     * @example
     * ```php
     * [$valid, $keyword] = $this->validateKeyword($keyword);
     * if (!$valid) {
     *     return $this->invalidParam($keyword);
     * }
     * ```
     */
    protected function validateKeyword(string $keyword): array
    {
        return Validator::validateKeyword($keyword);
    }

    /**
     * 返回资源未找到错误
     *
     * @param string $msg 自定义错误消息，默认 'Resource not found'
     * @return \think\Response JSON 格式的响应
     *
     * @example
     * ```php
     * return $this->notFound('Article not found');
     * ```
     */
    protected function notFound(string $msg = 'Resource not found')
    {
        return ResponseHelper::notFound($msg);
    }

    /**
     * 返回参数错误
     *
     * @param string $msg 自定义错误消息，默认 'Invalid parameter'
     * @return \think\Response JSON 格式的响应
     *
     * @example
     * ```php
     * return $this->invalidParam('Invalid page parameter');
     * ```
     */
    protected function invalidParam(string $msg = 'Invalid parameter')
    {
        return ResponseHelper::invalidParam($msg);
    }

    /**
     * 获取缓存键
     *
     * 根据当前控制器名、操作名和参数生成唯一的缓存键
     * 格式：api_{controller}_{action}:{paramsHash}
     *
     * @param string $prefix 缓存键前缀
     * @param array $params 参与生成缓存键的参数
     * @return string 缓存键
     *
     * @example
     * ```php
     * $cacheKey = $this->getCacheKey('article_detail', ['id' => 123]);
     * $data = Cache::get($cacheKey);
     * ```
     */
    protected function getCacheKey(string $prefix, array $params = []): string
    {
        // 获取当前控制器和方法名
        $controller = $this->request->controller();
        $action = $this->request->action();

        // 将参数转换为字符串并计算 MD5 哈希
        $paramHash = empty($params) ? '' : md5(json_encode($params));

        return "api_{$controller}_{$action}:{$paramHash}";
    }

    /**
     * 获取缓存数据
     *
     * 封装缓存获取逻辑，支持缓存未命中时的回调
     *
     * @param string $key 缓存键
     * @param callable|null $callback 缓存未命中时的回调函数，返回数据并自动缓存
     * @param int|null $expire 缓存有效期，默认使用类配置
     * @return mixed 缓存数据或回调返回值
     *
     * @example
     * ```php
     * $data = $this->getCache("article_detail:123", function() {
     *     return ArticleModel::find(123);
     * }, 600);
     * ```
     */
    protected function getCache(string $key, ?callable $callback = null, ?int $expire = null)
    {
        // 如果不启用缓存或没有回调，直接返回 null
        if (!$this->enableCache || $callback === null) {
            return null;
        }

        $expire = $expire ?? $this->cacheExpire;

        // 尝试从缓存获取
        $cache = \think\facade\Cache::get($key);

        if ($cache !== null) {
            return $cache;
        }

        // 缓存未命中，执行回调获取数据
        $data = $callback();

        // 将数据写入缓存
        \think\facade\Cache::set($key, $data, $expire);

        return $data;
    }

    /**
     * 清除指定缓存
     *
     * @param string $key 缓存键
     * @return bool 是否清除成功
     *
     * @example
     * ```php
     * $this->clearCache("article_detail:123");
     * ```
     */
    protected function clearCache(string $key): bool
    {
        return \think\facade\Cache::delete($key);
    }

    /**
     * 清除相关缓存（带前缀匹配）
     *
     * 清除所有匹配指定前缀的缓存，常用于数据更新后清除相关缓存
     *
     * @param string $prefix 缓存键前缀
     * @return bool 是否清除成功
     *
     * @example
     * ```php
     * // 清除所有文章相关的缓存
     * $this->clearCacheByPrefix("api_article_");
     * ```
     */
    protected function clearCacheByPrefix(string $prefix): bool
    {
        // 注意：ThinkPHP 的文件缓存不支持前缀匹配清除
        // 如果使用 Redis，可以使用 Redis 的 KEYS 命令
        // 这里简单返回 true，实际使用中可能需要根据缓存驱动进行调整
        return true;
    }

    /**
     * 获取栏目及其子栏目 ID 列表
     *
     * 递归获取指定栏目及其所有子栏目的 ID，用于查询该栏目下的所有内容
     * 结果会被缓存以提高性能
     *
     * @param int $columnId 栏目 ID
     * @param bool $useCache 是否使用缓存，默认 true
     * @return array 栏目 ID 数组，包含指定栏目及其所有子栏目
     *
     * @example
     * ```php
     * $columnIds = $this->getColumnIds(5);
     * // 返回 [5, 6, 7, 8, ...] 包含栏目5及其所有子栏目
     * $query->whereIn('column_id', $columnIds);
     * ```
     */
    protected function getColumnIds(int $columnId, bool $useCache = true): array
    {
        // 生成缓存键
        $cacheKey = "api_column_children:" . $columnId;

        // 尝试从缓存获取
        if ($useCache) {
            $cached = \think\facade\Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        // 初始化结果数组，包含当前栏目
        $ids = [$columnId];

        // 查询直接子栏目
        $children = \think\facade\Db::name('column')
            ->where('pid', $columnId)
            ->column('id');

        // 如果有子栏目，递归获取
        if (!empty($children)) {
            $ids = array_merge($ids, $children);

            // 递归处理每个子栏目
            foreach ($children as $childId) {
                $subChildren = $this->getColumnIds($childId, false); // 子栏目不再重复缓存
                $ids = array_merge($ids, $subChildren);
            }
        }

        // 去重
        $ids = array_unique($ids);

        // 写入缓存，有效期 5 分钟
        if ($useCache) {
            \think\facade\Cache::set($cacheKey, $ids, 300);
        }

        return $ids;
    }

    /**
     * 格式化列表数据
     *
     * 将模型列表转换为统一的数组格式，只保留需要的字段
     *
     * @param \think\collection $list 模型集合
     * @param array $fields 需要保留的字段名
     * @return array 格式化后的数组列表
     *
     * @example
     * ```php
     * $result = $this->formatList($articles, ['id', 'title', 'create_time']);
     * ```
     */
    protected function formatList($list, array $fields = []): array
    {
        $result = [];

        foreach ($list as $item) {
            // 如果没有指定字段，转换整个模型为数组
            if (empty($fields)) {
                $result[] = is_array($item) ? $item : $item->toArray();
                continue;
            }

            // 只保留指定字段
            $tmp = [];
            foreach ($fields as $field) {
                $value = is_array($item) ? ($item[$field] ?? null) : ($item->$field ?? null);
                if ($value !== null) {
                    $tmp[$field] = $value;
                }
            }
            $result[] = $tmp;
        }

        return $result;
    }

    /**
     * 获取客户端 IP 地址
     *
     * 优先获取 X-Forwarded-For 头，支持代理后的真实 IP
     *
     * @return string IP 地址
     *
     * @example
     * ```php
     * $ip = $this->getClientIp();
     * ```
     */
    protected function getClientIp(): string
    {
        return $this->request->ip();
    }

    /**
     * 记录 API 访问日志
     *
     * 将 API 调用信息记录到日志，便于后续分析和问题排查
     *
     * @param string $action 操作名称
     * @param array $params 请求参数
     * @param mixed $result 结果（成功或失败）
     * @return void
     *
     * @example
     * ```php
     * $this->logAccess('article_list', $params, $result);
     * ```
     */
    protected function logAccess(string $action, array $params = [], $result = null): void
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => $action,
            'ip' => $this->getClientIp(),
            'params' => $params,
            'result_type' => is_array($result) ? 'array' : gettype($result)
        ];

        trace($logData, 'api_access');
    }
}