<?php

namespace app\validate;

use think\Validate;

class BaseValidate extends Validate
{
    protected $allowed_params = ['activepath'];

    /**
     * 验证失败时返回400错误并终止
     */
    protected function handleValidationError()
    {
        require_once('./static/405/405.html');
        exit();
    }

    /**
     * 宽松模式验证允许特定字符和.css/.html后缀
     * @param string $input 原始输入值
     */
    public function validateWhitelistInput($input)
    {
        if (!preg_match('/^[a-zA-Z0-9_.\/-]+(\.css|\.html)?$/', $input)) {
            $this->handleValidationError();
        }
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * 严格模式验证禁止路径遍历符号
     * @param string $input 原始输入值
     */
    public function validateStrictInput($input)
    {
        if (!preg_match('/^(?!.*\.\.)(?!.*\.\/)(?!.*\/\.)[\p{L}\p{N}_,\-\/.=%]*$/u', $input)) {
            $this->handleValidationError();
        }
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * 中等模式验证（适用于非白名单参数）
     * @param string $input 原始输入值
     */
    public function validateMediumInput($input)
    {
        // 处理数组类型输入（支持多维数组）
        if (is_array($input)) {
            return array_map([$this, 'validateMediumInput'], $input);
        }

        // 处理非字符串类型
        if (!is_string($input)) {
            return $input;
        }

        // 解码 HTML 实体
        $decodedInput = html_entity_decode($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        // ▼▼▼ 新增SQL注入防护 ▼▼▼
        $sqlInjectionPatterns = [
            '/(union[\s]+select)/i',        // UNION SELECT 注入
            '/(select|update|delete|insert)[\s]+.*from/i', // SQL操作语句
            '/\b(?:drop|alter|truncate|rename|create\s+(?:table|database|index|view|procedure|function))\b/i', // 数据库结构操作
            '/\b(?:sleep|benchmark)\b\(/i', // 时间盲注函数
            '/\b0x[a-f0-9]{2,}\b/i', // 十六进制编码
            '/[\'";](\s*--|#|\/\*)/i', // 注释符
            '/\b(?:or|and)\b\s+[\d\w]+\s*=\s*[\d\w]+/i' // 永真条件
        ];

        foreach ($sqlInjectionPatterns as $pattern) {
            if (preg_match($pattern, $decodedInput)) {
                $this->handleValidationError();
            }
        }

        // 过滤掉 <script> 标签，但保留其内部内容
        $filteredInput = preg_replace_callback('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/is', function ($matches) {
            // 移除 <script> 和 </script> 标签，保留内部内容
            return preg_replace('/<\/?script\b[^>]*>/is', '', $matches[0]);
        }, $decodedInput);

        // 过滤掉 javascript: 关键字
        $filteredInput = preg_replace('/javascript:/i', '', $filteredInput);

        // 过滤掉 onXXX= 属性
        $filteredInput = preg_replace('/\s+on\w+=(["\'][^"\']*["\'])/i', '', $filteredInput);

        return $filteredInput;
    }
    /**
     * 验证所有GET参数（白名单参数用宽松模式）
     */
    public function validateGetParams()
    {
        foreach ($_GET as $key => $value) {
            in_array($key, $this->allowed_params)
                ? $this->validateWhitelistInput($value)
                : $this->validateStrictInput($value);
        }
    }

    /**
     * 验证所有Cookie参数（白名单参数用宽松模式）
     */
    public function validateCookieParams()
    {
        foreach ($_COOKIE as $key => $value) {
            in_array($key, $this->allowed_params)
                ? $this->validateWhitelistInput($value)
                : $this->validateStrictInput($value);
        }
    }

    /**
     * 验证POST请求的所有参数（白名单参数用宽松模式）
     */
    public function validatePostData()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return;
        }
        // 通过URL路径判断是否需要跳过验证
        $pathInfo = $_SERVER['PATH_INFO'] ?? '';
        if ($this->shouldSkipValidation($pathInfo)) {
            return;
        }
        // 通过完整URL路径判断
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        if ($this->shouldSkipByUri($requestUri)) {
            return;
        }
        foreach ($_POST as $key => $value) {
            $_POST[$key] = $this->validateMediumInput($value); // 统一验证方式
        }
    }

    /**
     * 验证HTTP Referer头（安全增强版）
     * @param array $options 配置选项
     * @return string|null 验证通过返回 Referer，否则返回 null
     */
    public function validateReferer($options = [])
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? null;

        // 1. 空值检查
        if (empty($referer) || !is_string($referer)) {
            return null;
        }

        // 2. 长度限制（防止 DoS）
        $maxLength = $options['max_length'] ?? 2048;
        if (mb_strlen($referer) > $maxLength) {
            return null;
        }

        // 3. 去除控制字符和不可见字符
        $referer = preg_replace('/[\x00-\x1F\x7F]/', '', $referer);
        $referer = trim($referer);

        // 4. 协议验证（白名单）
        if (!preg_match('/^https?:\/\//i', $referer)) {
            return null;
        }

        // 5. 禁止危险协议（多次解码防护）
        for ($i = 0; $i < 3; $i++) {
            $decoded = urldecode($referer);
            if ($decoded === $referer) break;
            $referer = $decoded;
        }

        $dangerousPatterns = [
            'javascript:',
            'data:',
            'vbscript:',
            'livescript:',
            'expression(',
        ];

        foreach ($dangerousPatterns as $pattern) {
            if (stripos($referer, $pattern) !== false) {
                return null;
            }
        }

        // 6. 禁止事件处理器属性
        if (preg_match('/\s+on\w+\s*=/i', $referer)) {
            return null;
        }

        // 7. URL 格式校验
        if (!filter_var($referer, FILTER_VALIDATE_URL)) {
            return null;
        }

        // 8. 解析域名
        $host = parse_url($referer, PHP_URL_HOST);
        if (empty($host)) {
            return null;
        }

        // 9. 禁止内网/私有 IP（防止 SSRF + Referer 泄露）
        if ($this->isPrivateHost($host)) {
            return null;
        }

        // 10. 字符白名单（确保只包含合法 URL 字符）
        if (!preg_match('/^[a-zA-Z0-9\-\.\/:~%_?=&;#+]+$/', $referer)) {
            // 允许 @ 符号（邮箱格式链接）
            if (!preg_match('/^[a-zA-Z0-9\-\.\/:~%_?=&;#+@]+$/', $referer)) {
                return null;
            }
        }

        return $referer;
    }

    /**
     * 检测是否为私有/内网地址
     * @param string $host 主机名或 IP
     * @return bool
     */
    private function isPrivateHost($host)
    {
        // 去除端口
        $host = explode(':', $host)[0];

        // 过滤掉明显非法的host
        if (empty($host) || strlen($host) > 253) {
            return true;
        }

        // 1. 保留IP检测
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
            return false; // 是公网 IP
        }

        // 2. localhost 检测
        $localhostPatterns = ['localhost', '127.0.0.1', '::1', '0.0.0.0'];
        if (in_array($host, $localhostPatterns)) {
            return true;
        }

        // 3. 内网 IP 段检测
        $privateRanges = [
            '/^10\./',                           // 10.0.0.0 - 10.255.255.255
            '/^172\.(1[6-9]|2\d|3[01])\./',     // 172.16.0.0 - 172.31.255.255
            '/^192\.168\./',                    // 192.168.0.0 - 192.168.255.255
            '/^169\.254\./',                    // 链路本地地址
            '/^fc00:/i',                        // IPv6 私有
            '/^fe80:/i',                        // IPv6 链路本地
        ];

        foreach ($privateRanges as $pattern) {
            if (preg_match($pattern, $host)) {
                return true;
            }
        }

        // 4. 本地主机名检测
        if (preg_match('/^(localhost|local|host|server)$/i', $host)) {
            return true;
        }

        return false;
    }

    /**
     * 根据完整URL特征判断是否跳过验证
     */
    private function shouldSkipByUri($uri)
    {
        // 匹配特征路径（示例URL中的关键部分）
        $patterns = [
            '/\/template_file\/editFile\.html/',
            '/\/template_file\/addFile\.html/'
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $uri)) {
                return true;
            }
        }
        return false;
    }
}
