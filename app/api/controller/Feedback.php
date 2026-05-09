<?php

declare(strict_types=1);
/**
 * 用户反馈 API 控制器
 *
 * 提供用户反馈相关的 API 接口：
 * - 提交反馈
 * - 获取反馈列表
 * - 获取反馈详情
 * - 获取反馈统计
 *
 * @package app\api\controller
 * @author  Team
 * @version 1.0
 */
namespace app\api\controller;

use app\common\model\Feedback as FeedbackModel;
use think\facade\Db;

/**
 * 用户反馈 API 控制器
 *
 * @extends ApiBase
 */
class Feedback extends ApiBase
{
    /**
     * 默认缓存有效期（秒）
     *
     * @var int
     */
    protected $cacheExpire = 300;

    /**
     * 获取反馈列表
     *
     * 支持分页、语言筛选、类型筛选
     *
     * @route GET /api/feedback/list
     * @param page int 可选 页码，默认 1
     * @param pageSize int 可选 每页数量，默认 15，最大 100
     * @param lang string 可选 语言标识
     * @param type string 可选 反馈类型
     * @param status string 可选 处理状态：'pending'、'processing'、'resolved'
     * @return json
     *
     * @example
     * GET /api/feedback/list?page=1&pageSize=10&lang=zh
     */
    public function list()
    {
        // 获取分页参数
        [$page, $size] = $this->getPageParams();
        if (!$page) {
            return $this->invalidParam($size);
        }

        // 获取筛选参数
        $lang = $this->request->param('lang', '');
        $type = $this->request->param('type', '');
        $status = $this->request->param('status', '');

        // 验证语言参数
        if (!empty($lang)) {
            [$valid, $lang] = $this->validateLang($lang);
            if (!$valid) {
                return $this->invalidParam($lang);
            }
        }

        // 构建基础查询
        $query = FeedbackModel::where('status', '>=', 0);

        // 语言筛选
        if (!empty($lang)) {
            $query->where('lang', $lang);
        }

        // 类型筛选
        if (!empty($type)) {
            $query->where('type', $type);
        }

        // 状态筛选
        if (!empty($status)) {
            $query->where('status', $status);
        }

        // 执行分页查询
        $result = $this->paginate($query, $page, $size);

        // 格式化列表数据
        $result['list'] = $this->formatFeedbackList($result['list']);

        return $this->success($result);
    }

    /**
     * 获取反馈详情
     *
     * 根据反馈 ID 获取反馈的完整信息
     *
     * @route GET /api/feedback/detail
     * @param id int 必填 反馈 ID
     * @return json
     *
     * @example
     * GET /api/feedback/detail?id=123
     */
    public function detail()
    {
        $id = intval($this->request->param('id', 0));

        // 验证 ID 参数
        [$valid, $id] = $this->validateId($id);
        if (!$valid) {
            return $this->invalidParam($id);
        }

        // 生成缓存键
        $cacheKey = "api_feedback_detail:" . $id;

        // 尝试从缓存获取
        $cache = \think\facade\Cache::get($cacheKey);
        if ($cache !== null) {
            return $this->success($cache);
        }

        // 从数据库查询反馈
        $feedback = FeedbackModel::find($id);

        // 检查是否存在
        if (!$feedback) {
            return $this->notFound('Feedback not found');
        }

        // 组装返回数据
        $result = [
            'id' => $feedback['id'],
            'type' => $feedback['type'],
            'title' => $feedback['title'],
            'content' => $feedback['content'],
            'contact' => $feedback['contact'] ?? '',
            'email' => $feedback['email'] ?? '',
            'phone' => $feedback['phone'] ?? '',
            'status' => $feedback['status'],
            'reply' => $feedback['reply'] ?? '',
            'replyTime' => $feedback['reply_time'] ?? '',
            'createTime' => $feedback['create_time'],
            'lang' => $feedback['lang']
        ];

        // 缓存结果
        \think\facade\Cache::set($cacheKey, $result, $this->cacheExpire);

        return $this->success($result);
    }

    /**
     * 获取反馈统计
     *
     * 返回各类型和状态的反馈数量统计
     *
     * @route GET /api/feedback/statistics
     * @param lang string 可选 语言标识
     * @return json
     *
     * @example
     * GET /api/feedback/statistics?lang=zh
     */
    public function statistics()
    {
        $lang = $this->request->param('lang', '');

        if (!empty($lang)) {
            [$valid, $lang] = $this->validateLang($lang);
            if (!$valid) {
                return $this->invalidParam($lang);
            }
        }

        // 生成缓存键
        $cacheKey = "api_feedback_stats:" . $lang;

        // 尝试从缓存获取
        $cache = \think\facade\Cache::get($cacheKey);
        if ($cache !== null) {
            return $this->success($cache);
        }

        // 统计总数
        $totalQuery = FeedbackModel::where('status', '>=', 0);
        if (!empty($lang)) {
            $totalQuery->where('lang', $lang);
        }
        $total = $totalQuery->count();

        // 统计待处理
        $pendingQuery = FeedbackModel::where('status', 0);
        if (!empty($lang)) {
            $pendingQuery->where('lang', $lang);
        }
        $pending = $pendingQuery->count();

        // 统计处理中
        $processingQuery = FeedbackModel::where('status', 1);
        if (!empty($lang)) {
            $processingQuery->where('lang', $lang);
        }
        $processing = $processingQuery->count();

        // 统计已解决
        $resolvedQuery = FeedbackModel::where('status', 2);
        if (!empty($lang)) {
            $resolvedQuery->where('lang', $lang);
        }
        $resolved = $resolvedQuery->count();

        $result = [
            'total' => (int)$total,
            'pending' => (int)$pending,
            'processing' => (int)$processing,
            'resolved' => (int)$resolved
        ];

        // 缓存结果
        \think\facade\Cache::set($cacheKey, $result, 300);

        return $this->success($result);
    }

    /**
     * 格式化反馈列表数据
     *
     * @param \think\Collection $list 反馈模型集合
     * @return array 格式化后的数组列表
     *
     * @internal
     */
    private function formatFeedbackList($list): array
    {
        $result = [];

        foreach ($list as $item) {
            $result[] = [
                'id' => $item['id'],
                'type' => $item['type'],
                'title' => $item['title'],
                'content' => mb_substr(strip_tags($item['content'] ?? ''), 0, 100),
                'contact' => $item['contact'] ?? '',
                'email' => $item['email'] ?? '',
                'status' => $item['status'],
                'createTime' => $item['create_time']
            ];
        }

        return $result;
    }
}