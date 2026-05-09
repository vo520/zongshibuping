<?php

/**
 * @Descripttion : FOXCMS 是一款高效的 PHP 多端跨平台内容管理系统
 * @Author : Peter
 * @Date : 2023/6/26   19:22
 * @version : V1.08
 * @copyright : ©2026
 * @LastEditTime : 2023/6/26   19:22
 */

namespace app\plus\controller;

use app\common\model\AccessStat;
use think\Response;

// 统计
class Access
{

    private $limitTime = 5; // 基础限制时间（秒）
    private $maxPenalty = 86400; // 最大惩罚时间（24小时）

    public function stat()
    {
        // 获取客户端指纹（IP+UA+协议头混合特征）
        $clientFingerprint = md5(
            getAccessIP() .
                ($_SERVER['HTTP_USER_AGENT'] ?? 'unknown') .
                ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'en')
        );

        // 生成复合缓存键
        $rateKey = "access_rate:" . $clientFingerprint;
        $penaltyKey = "access_penalty:" . $clientFingerprint;

        // 检查当前是否处于惩罚期
        if ($penaltyTime = saveToCache($penaltyKey)) {
            $remaining = $penaltyTime - time();
            return Response::create("请求受限，请{$remaining}秒后重试", "html", 429);
        }

        // 智能限流逻辑
        $lastAccess = saveToCache($rateKey);
        $currentTime = time();

        if ($lastAccess && ($currentTime - $lastAccess) < $this->limitTime) {
            // 动态计算惩罚时间（指数退避）
            $violations = saveToCache($rateKey . '_count') ?: 0;
            $penaltyDuration = min(
                $this->limitTime * pow(2, $violations),
                $this->maxPenalty
            );

            // 记录惩罚状态
            saveToCache($penaltyKey, $currentTime + $penaltyDuration, $penaltyDuration);
            saveToCache($rateKey . '_count', $violations + 1, 86400); // 24小时计数

            // 返回标准化响应
            header('Retry-After: ' . $penaltyDuration);
            return Response::create("请求过于频繁，请{$penaltyDuration}秒后重试", "html", 429);
        }

        // 正常请求处理
        saveToCache($rateKey, $currentTime, 300); // 5分钟时间窗
        saveToCache($rateKey . '_count', 0, 86400); // 重置违规计数

        // 以下是原有的业务逻辑
        $ip = getAccessIP();
        $k = "access_" . str_replace(".", "_", $ip);

        // 请求过滤拦截
        // 从缓存中获取上次访问的时间戳
        $timestamp1 = saveToCache($k);
        if ($timestamp1 != null) {
            // 计算当前时间与上次访问时间的时间差
            $timestampArr = time_diff($timestamp1, time());
            $hours = $timestampArr["hours"]; // 小时
            $minutes = $timestampArr["minutes"]; // 分钟
            $seconds = $timestampArr["seconds"]; // 秒
            // 如果时间差小于限制时间（默认5秒），则认为是频繁提交
            if ($hours <= 0 && $minutes <= 0 && $seconds < $this->limitTime) {
                $content =  "频繁提交,请稍候再试";
                $type = "html";
                header("Content-type: text/html; charset=utf-8");
                // 返回响应，提示用户稍后再试
                return Response::create($content, $type, 0);
            }
        }

        // 从cookie中获取访问信息
        $cookie = cookie($k);
        if (empty($cookie)) {
            // 如果cookie为空，则设置一个新的cookie，有效期到当天晚上12点
            $saveTime = strtotime(date("Y-m-d", time()) . " 23:59:59");
            setcookie($k, (string)time(), $saveTime, "/");
            $cookie = "first";
        } else {
            $cookie = "continue";
        }

        // ========== 安全修复：统一处理来源页面 ==========
        // 优先级：GET参数'fp' > HTTP_REFERER > 空值
        $from_page = $this->sanitizeFromPage();

        // 判断访问设备类型
        $mobileText = "计算机端浏览器";
        if (is_mobile()) {
            $mobileText = "移动端浏览器";
        }

        // 获取搜索引擎关键词及来源信息（安全处理）
        $word = $this->sanitizeSearchWord(search_word_from());
        if (!empty($word['keyword'])) {
            // 如果存在关键词，则输出相关信息（已转义）
            echo '关键字：' . htmlspecialchars($word['keyword'], ENT_QUOTES, 'UTF-8') . ' 来自：' . htmlspecialchars($word['from'], ENT_QUOTES, 'UTF-8');
        }

        // 获取来源页面的标题（安全处理）
        $page_title = $this->sanitizePageTitle();

        // 设置查询时间范围为当天的00:00:00到23:59:59
        $startTime = date("Y-m-d") . " 00:00:00";
        $endTime = date("Y-m-d") . " 23:59:59";
        // 获取浏览器信息
        $browser = getBrowser();
        // 查询数据库中是否存在相同IP、来源页面和浏览器的访问记录
        $as = AccessStat::where([['ip', '=', $ip], ['from_page', '=', $from_page], ['browser', '=', $browser]])->whereBetweenTime("create_time", $startTime, $endTime)->find();
        if ($as) {
            $type = "html";
            $content = "'存在" . $cookie . "'";
            header("Content-type: text/html; charset=utf-8");
            // 如果存在记录，则返回响应，提示记录已存在
            return Response::create($content, $type, 200);
        }
        // 构建新的访问记录数据
        $accessStat = ["cookie" => $cookie, "ip" => $ip, "browser" => $browser, "from_page" => $from_page, "source_site" => $word['from'], "browser_type" => $mobileText, 'page_title' => $page_title];
        try {
            // 将新的访问记录保存到数据库
            AccessStat::create($accessStat);
            // 记录当前时间到缓存中
            saveToCache($k, time());
        } catch (\Exception $e) {
            $type = "html";
            $content = "'" . $e->getMessage() . "'";
            // 如果保存过程中发生异常，则返回响应，提示错误信息
            return Response::create($content, $type, 200);
        }
        $type = "html";
        $content = "'成功" . $cookie . "'";
        header("Content-type: text/html; charset=utf-8");
        // 返回响应，提示记录保存成功
        return Response::create($content, $type, 200);
    }

    /**
     * 安全过滤：校验并清理来源页面 URL
     * @return string 过滤后的安全URL
     */
    private function sanitizeFromPage()
    {
        $url = '';

        // 优先级1：GET参数 'fp'
        if (isset($_GET['fp']) && is_string($_GET['fp'])) {
            $url = $_GET['fp'];
        }
        // 优先级2：HTTP_REFERER
        elseif (!empty($_SERVER['HTTP_REFERER'])) {
            $url = $_SERVER['HTTP_REFERER'];
        }

        // 空值处理
        if (empty($url)) {
            return '';
        }

        // 去除空白字符
        $url = trim($url);

        // 仅允许 http/https 协议，禁止 javascript: 等危险协议
        if (!preg_match('/^https?:\/\//i', $url)) {
            return '';
        }

        // 禁止危险关键字（双编码绕过防护）
        $decoded = urldecode($url);
        $dangerousPatterns = [
            'javascript:',
            'data:',
            'vbscript:',
            'onerror=',
            'onload=',
            'onclick=',
            'onmouseover=',
            'onfocus=',
            'onblur=',
            'expression\(',
            'style=',
        ];
        foreach ($dangerousPatterns as $pattern) {
            if (stripos($decoded, $pattern) !== false) {
                return '';
            }
        }

        // URL 格式校验
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        // 限制长度（防止过长 payload）
        $url = mb_substr($url, 0, 2048);

        return $url;
    }

    /**
     * 安全过滤：校验并清理页面标题
     * @return string 过滤后的安全标题
     */
    private function sanitizePageTitle()
    {
        $title = '未获取到标题';

        // 优先使用 GET 参数中的 title
        if (isset($_GET['title']) && !empty($_GET['title'])) {
            $title = $_GET['title'];
        }
        // 备用：从来源页面抓取
        else {
            $from_page = $this->sanitizeFromPage();
            if (!empty($from_page)) {
                $title = getPageTitle($from_page);
            }
        }

        // 空值处理
        if (empty($title) || !is_string($title)) {
            return '未获取到标题';
        }

        // HTML 实体转义
        $title = htmlspecialchars($title, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 过滤危险标签和属性
        $title = $this->stripDangerousHtml($title);

        // 限制标题长度
        $title = mb_substr(trim($title), 0, 255);

        return $title;
    }

    /**
     * 安全过滤：校验并清理搜索引擎关键词
     * @param array $word 原始关键词数据
     * @return array 过滤后的安全数据
     */
    private function sanitizeSearchWord($word)
    {
        $safeWord = ['keyword' => '', 'from' => ''];

        if (!is_array($word)) {
            return $safeWord;
        }

        // 安全过滤关键词
        if (isset($word['keyword']) && is_string($word['keyword'])) {
            $keyword = trim($word['keyword']);
            // HTML 实体转义
            $keyword = htmlspecialchars($keyword, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            // 限制长度
            $keyword = mb_substr($keyword, 0, 255);
            $safeWord['keyword'] = $keyword;
        }

        // 安全过滤来源
        if (isset($word['from']) && is_string($word['from'])) {
            $from = trim($word['from']);
            // 只允许字母
            $from = preg_replace('/[^a-zA-Z]/', '', $from);
            $safeWord['from'] = $from;
        }

        return $safeWord;
    }

    /**
     * 过滤危险的 HTML 标签和属性
     * @param string $input 原始输入
     * @return string 过滤后的安全字符串
     */
    private function stripDangerousHtml($input)
    {
        // 移除 script 标签
        $input = preg_replace('/<\/?script\b[^>]*>/i', '', $input);
        // 移除 style 标签和属性
        $input = preg_replace('/<\/?style\b[^>]*>/i', '', $input);
        // 移除 on* 事件属性
        $input = preg_replace('/\s+on\w+\s*=\s*(["\'])[^"\']*\1/i', '', $input);
        $input = preg_replace('/\s+on\w+\s*=\s*[^\s>]+/i', '', $input);
        // 移除 javascript: 协议
        $input = preg_replace('/javascript\s*:/i', '', $input);
        // 移除 data: 协议
        $input = preg_replace('/data\s*:/i', '', $input);

        return $input;
    }
}
