<?php

declare(strict_types=1);

namespace app\api\middleware;

use app\api\lib\ResponseHelper;
use think\Request;

class SignatureAuth
{
    protected $excludes = [
        'api/column/list',
        'api/column/tree',
        'api/article/list',
        'api/article/recommend',
        'api/article/hot',
        'api/product/list',
        'api/product/recommend',
        'api/images/list',
        'api/video/list',
        'api/download/list',
        'api/other/slides',
        'api/other/links',
        'api/other/tags',
        'api/other/nav',
        'api/other/config',
    ];

    protected $timeout = 300;

    public function handle($request, \Closure $next)
    {
        $path = $request->pathinfo();

        if ($this->isExcluded($path)) {
            return $next($request);
        }

        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');
        $appId = $request->header('X-App-Id');

        if (empty($signature) || empty($timestamp) || empty($appId)) {
            return ResponseHelper::invalidSignature('Missing authentication headers');
        }

        if (!$this->validateTimestamp($timestamp)) {
            return ResponseHelper::invalidSignature('Request timestamp expired');
        }

        if (!$this->validateSignature($request, $signature, $timestamp, $appId)) {
            return ResponseHelper::invalidSignature('Invalid signature');
        }

        $request->withMiddleware([
            'app_id' => $appId,
            'timestamp' => $timestamp
        ]);

        return $next($request);
    }

    protected function isExcluded(string $path): bool
    {
        foreach ($this->excludes as $pattern) {
            if (strpos($path, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function validateTimestamp(string $timestamp): bool
    {
        if (!is_numeric($timestamp)) {
            return false;
        }
        $requestTime = intval($timestamp);
        $now = time();
        return abs($now - $requestTime) <= $this->timeout;
    }

    protected function validateSignature(Request $request, string $signature, string $timestamp, string $appId): bool
    {
        $secret = $this->getSecret($appId);
        if (empty($secret)) {
            return false;
        }

        $params = $request->param();
        ksort($params);
        $paramString = http_build_query($params);

        $signString = $appId . $timestamp . $paramString . $secret;
        $expectedSignature = strtolower(md5($signString));

        return hash_equals($expectedSignature, strtolower($signature));
    }

    protected function getSecret(string $appId): string
    {
        $secrets = config('api.app_secrets', []);
        return $secrets[$appId] ?? '';
    }
}