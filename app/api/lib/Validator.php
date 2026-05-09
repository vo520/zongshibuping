<?php

declare(strict_types=1);
/**
 * 验证器工具类
 *
 * 提供常用的参数验证方法，包括：
 * - ID 验证
 * - 语言代码验证
 * - 关键词验证
 * - 分页参数验证
 * - 邮箱验证
 * - URL 验证
 * - 手机号验证
 *
 * @package app\api\lib
 * @author  Team
 * @version 1.0
 */
namespace app\api\lib;

use think\facade\Validate;

/**
 * 参数验证工具类
 *
 * 提供统一的参数验证入口，返回 [bool, mixed] 格式的验证结果
 */
class Validator
{
    /**
     * 验证器实例
     *
     * @var Validate
     */
    protected $validate;

    /**
     * 错误信息
     *
     * @var array
     */
    protected $errors = [];

    /**
     * 构造函数
     *
     * 初始化验证规则和错误消息
     *
     * @param array $rules 验证规则数组
     * @param array $messages 错误消息数组
     */
    public function __construct(array $rules = [], array $messages = [])
    {
        // 如果提供了规则，初始化验证器
        if (!empty($rules)) {
            $this->validate = Validate::rule($rules)->message($messages);
        }
    }

    /**
     * 执行验证
     *
     * 使用预设的验证器检查数据
     *
     * @param array $data 待验证的数据
     * @return bool 验证是否通过
     */
    public function check(array $data): bool
    {
        // 重置错误
        $this->errors = [];

        // 执行验证
        if (!$this->validate->check($data)) {
            // 获取错误信息
            $this->errors = $this->validate->getError();
            return false;
        }

        return true;
    }

    /**
     * 获取验证错误
     *
     * @return array 错误信息数组
     */
    public function getErrors(): array
    {
        // 确保返回数组格式
        return is_array($this->errors) ? $this->errors : [$this->errors];
    }

    /**
     * 创建验证器实例（静态方法）
     *
     * @param array $rules 验证规则
     * @param array $messages 错误消息
     * @return self
     *
     * @example
     * ```php
     * $validator = Validator::make([
     *     'id' => 'require|number',
     *     'name' => 'require|length:2,20'
     * ]);
     * ```
     */
    public static function make(array $rules, array $messages = []): self
    {
        return new self($rules, $messages);
    }

    /**
     * 验证 ID 参数
     *
     * 确保传入的 ID 是有效的正整数
     * 用于验证资源 ID、栏目 ID 等
     *
     * @param mixed $id 待验证的 ID 值
     * @return array [bool, mixed] 验证成功返回 [true, intId]，失败返回 [false, errorMessage]
     *
     * @example
     * ```php
     * [$valid, $id] = Validator::validateId($request->param('id'));
     * if (!$valid) {
     *     return $this->error($id);
     * }
     * ```
     */
    public static function validateId($id): array
    {
        // 将值转换为整数
        $id = intval($id);

        // 检查是否为正整数
        if ($id <= 0) {
            return [false, 'Invalid ID: must be a positive integer'];
        }

        return [true, $id];
    }

    /**
     * 验证语言代码参数
     *
     * 确保语言标识符格式正确
     * 格式要求：字母、数字、下划线、连字符组成
     *
     * @param string $lang 语言代码
     * @return array [bool, mixed] 验证成功返回 [true, validLang]，失败返回 [false, errorMessage]
     *
     * @example
     * ```php
     * [$valid, $lang] = Validator::validateLang('zh_cn');  // 通过
     * [$valid, $lang] = Validator::validateLang('');        // 通过（空字符串）
     * [$valid, $lang] = Validator::validateLang('zh cn');   // 失败（包含空格）
     * ```
     */
    public static function validateLang(string $lang): array
    {
        // 空字符串视为有效（使用默认语言）
        if (empty($lang)) {
            return [true, ''];
        }

        // 使用正则验证格式：字母、数字、下划线、连字符
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $lang)) {
            return [false, 'Invalid language code: only letters, numbers, underscore and hyphen are allowed'];
        }

        return [true, $lang];
    }

    /**
     * 验证搜索关键词
     *
     * 确保关键词非空且长度合理，并进行 XSS 过滤
     * 最大长度为 100 个字符
     *
     * @param string $keyword 搜索关键词
     * @return array [bool, mixed] 验证成功返回 [true, safeKeyword]，失败返回 [false, errorMessage]
     *
     * @example
     * ```php
     * [$valid, $keyword] = Validator::validateKeyword('test product');
     * if (!$valid) {
     *     return $this->error($keyword);
     * }
     * ```
     */
    public static function validateKeyword(string $keyword): array
    {
        // 去除首尾空白
        $keyword = trim($keyword);

        // 检查是否为空
        if (empty($keyword)) {
            return [false, 'Keyword cannot be empty'];
        }

        // 检查长度限制
        if (mb_strlen($keyword) > 100) {
            return [false, 'Keyword too long: maximum 100 characters allowed'];
        }

        // 进行 HTML 转义，防止 XSS 攻击
        $safeKeyword = htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8');

        return [true, $safeKeyword];
    }

    /**
     * 验证分页参数
     *
     * 确保 page 和 pageSize 在合理范围内
     * - page 必须 >= 1
     * - pageSize 必须 >= 1
     * - pageSize 不能超过最大限制
     *
     * @param int $page 页码
     * @param int $pageSize 每页数量
     * @param int $maxPageSize 最大每页数量，默认 100
     * @return array [bool, mixed] 验证成功返回 [true, []]，失败返回 [false, errorMessage]
     *
     * @example
     * ```php
     * [$valid, $error] = Validator::validatePageParams($page, $pageSize);
     * if (!$valid) {
     *     return $this->error($error);
     * }
     * ```
     */
    public static function validatePageParams(int $page, int $pageSize, int $maxPageSize = 100): array
    {
        // 验证 page 参数
        if ($page < 1) {
            return [false, 'Page must be greater than 0'];
        }

        // 验证 pageSize 参数
        if ($pageSize < 1) {
            return [false, 'PageSize must be greater than 0'];
        }

        // 验证 pageSize 不能超过最大限制
        if ($pageSize > $maxPageSize) {
            return [false, "PageSize cannot exceed {$maxPageSize}"];
        }

        return [true, []];
    }

    /**
     * 验证邮箱地址
     *
     * 使用正则验证邮箱格式
     *
     * @param string $email 邮箱地址
     * @return array [bool, mixed] 验证成功返回 [true, validEmail]，失败返回 [false, errorMessage]
     *
     * @example
     * ```php
     * [$valid, $email] = Validator::validateEmail('test@example.com');
     * ```
     */
    public static function validateEmail(string $email): array
    {
        $email = trim($email);

        if (empty($email)) {
            return [false, 'Email cannot be empty'];
        }

        // 使用 ThinkPHP 内置的邮箱验证
        if (!Validate::isEmail($email)) {
            return [false, 'Invalid email format'];
        }

        return [true, strtolower($email)];
    }

    /**
     * 验证手机号
     *
     * 支持中国大陆手机号格式
     * 格式：1开头的11位数字
     *
     * @param string $phone 手机号
     * @return array [bool, mixed] 验证成功返回 [true, validPhone]，失败返回 [false, errorMessage]
     *
     * @example
     * ```php
     * [$valid, $phone] = Validator::validatePhone('13812345678');
     * ```
     */
    public static function validatePhone(string $phone): array
    {
        $phone = trim($phone);

        if (empty($phone)) {
            return [false, 'Phone cannot be empty'];
        }

        // 验证中国大陆手机号格式
        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return [false, 'Invalid phone number format'];
        }

        return [true, $phone];
    }

    /**
     * 验证 URL
     *
     * 验证是否为有效的 URL 格式
     *
     * @param string $url URL 地址
     * @return array [bool, mixed] 验证成功返回 [true, validUrl]，失败返回 [false, errorMessage]
     *
     * @example
     * ```php
     * [$valid, $url] = Validator::validateUrl('https://example.com');
     * ```
     */
    public static function validateUrl(string $url): array
    {
        $url = trim($url);

        if (empty($url)) {
            return [false, 'URL cannot be empty'];
        }

        // 使用 ThinkPHP 内置的 URL 验证
        if (!Validate::isUrl($url)) {
            return [false, 'Invalid URL format'];
        }

        return [true, $url];
    }

    /**
     * 验证日期格式
     *
     * 验证日期字符串是否符合指定格式
     *
     * @param string $date 日期字符串
     * @param string $format 日期格式，默认 'Y-m-d'
     * @return array [bool, mixed] 验证成功返回 [true, validDate]，失败返回 [false, errorMessage]
     *
     * @example
     * ```php
     * [$valid, $date] = Validator::validateDate('2024-01-01', 'Y-m-d');
     * ```
     */
    public static function validateDate(string $date, string $format = 'Y-m-d'): array
    {
        $date = trim($date);

        if (empty($date)) {
            return [false, 'Date cannot be empty'];
        }

        // 解析日期
        $d = \DateTime::createFromFormat($format, $date);

        // 检查日期是否有效
        if (!$d || $d->format($format) !== $date) {
            return [false, "Invalid date format, expected: {$format}"];
        }

        return [true, $date];
    }

    /**
     * 清理 HTML 内容
     *
     * 移除危险的 HTML 标签和属性，仅保留安全的标签
     * 常用于用户提交的文章内容等
     *
     * @param string $content HTML 内容
     * @return string 清理后的内容
     *
     * @example
     * ```php
     * $safeContent = Validator::sanitizeHtml($userContent);
     * ```
     */
    public static function sanitizeHtml(string $content): string
    {
        // 移除所有 HTML 标签
        // 如需保留部分标签，可使用 strip_tags 并指定允许的标签
        return strip_tags($content, '<p><br><a><img><h1><h2><h3><h4><h5><h6><ul><ol><li><table><tr><td><th><strong><em><b><i>');
    }

    /**
     * 验证必填参数
     *
     * 检查数据数组中是否包含所有必需的字段
     *
     * @param array $data 数据数组
     * @param array $fields 必填字段列表
     * @return array [bool, mixed] 验证成功返回 [true, null]，失败返回 [false, missingFieldsString]
     *
     * @example
     * ```php
     * [$valid, $error] = Validator::validateRequired($params, ['id', 'title', 'content']);
     * if (!$valid) {
     *     return $this->error($error);
     * }
     * ```
     */
    public static function validateRequired(array $data, array $fields): array
    {
        $missing = [];

        // 遍历所有需要验证的字段
        foreach ($fields as $field) {
            // 检查字段是否存在且不为空
            if (!isset($data[$field]) || $data[$field] === '' || $data[$field] === null) {
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
     * 验证整数范围
     *
     * 确保整数值在指定范围内
     *
     * @param int $value 待验证的值
     * @param int $min 最小值
     * @param int $max 最大值
     * @return array [bool, mixed] 验证成功返回 [true, value]，失败返回 [false, errorMessage]
     *
     * @example
     * ```php
     * [$valid, $age] = Validator::validateRange($age, 0, 150);
     * ```
     */
    public static function validateRange(int $value, int $min, int $max): array
    {
        if ($value < $min || $value > $max) {
            return [false, "Value must be between {$min} and {$max}"];
        }

        return [true, $value];
    }
}