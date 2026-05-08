<?php

namespace app\api\controller;

use think\Request;

/**
 * API 版本控制器基类
 * 
 * 提供 API 版本控制和响应格式化
 */
abstract class ApiVersionController
{
    /**
     * 当前 API 版本
     */
    protected $apiVersion = 'v1';

    /**
     * 请求对象
     */
    protected $request;

    /**
     * 初始化
     */
    public function __construct(Request $request = null)
    {
        $this->request = $request ?? request();
        $this->parseApiVersion();
    }

    /**
     * 解析 API 版本
     */
    protected function parseApiVersion()
    {
        $path = $this->request->path();
        
        if (preg_match('/api\/v(\d+)/', $path, $matches)) {
            $this->apiVersion = 'v' . $matches[1];
        }
    }

    /**
     * 获取 API 版本
     */
    public function getApiVersion(): string
    {
        return $this->apiVersion;
    }

    /**
     * 成功响应
     */
    protected function success($data = null, string $msg = '操作成功', int $code = 200): \think\Response
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'version' => $this->apiVersion,
            'timestamp' => time(),
        ])->code($code);
    }

    /**
     * 错误响应
     */
    protected function error(string $msg = '操作失败', int $code = 400, $data = null): \think\Response
    {
        return json([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
            'version' => $this->apiVersion,
            'timestamp' => time(),
        ])->code($code);
    }

    /**
     * 分页响应
     */
    protected function paginate($data, int $total, int $page, int $pageSize): \think\Response
    {
        return json([
            'code' => 200,
            'msg' => 'success',
            'data' => [
                'list' => $data,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'page_size' => $pageSize,
                    'total_pages' => ceil($total / $pageSize),
                ],
            ],
            'version' => $this->apiVersion,
            'timestamp' => time(),
        ]);
    }

    /**
     * 验证请求参数
     */
    protected function validate(array $data, array $rules): bool
    {
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            if (is_string($rule)) {
                $rule = explode('|', $rule);
            }
            
            foreach ($rule as $r) {
                if (!$this->applyRule($r, $value)) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * 应用验证规则
     */
    protected function applyRule(string $rule, $value): bool
    {
        switch ($rule) {
            case 'require':
            case 'required':
                return $value !== null && $value !== '';
            case 'number':
            case 'numeric':
                return is_numeric($value);
            case 'integer':
                return filter_var($value, FILTER_VALIDATE_INT) !== false;
            case 'positive':
                return is_numeric($value) && $value > 0;
            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
            default:
                return true;
        }
    }
}