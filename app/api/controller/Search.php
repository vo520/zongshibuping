<?php

declare(strict_types=1);

namespace app\api\controller;

use think\facade\Db;

class Search extends ApiBase
{
    public function index()
    {
        try {
            $keyword = trim($this->request->param('keyword', ''));

            if (empty($keyword)) {
                return $this->invalidParam('Keyword is required');
            }

            $result = [];

            try {
                $articles = Db::name('article')->where('title', 'like', '%' . $keyword . '%')->limit(10)->select();
                $result['article'] = array_map(fn($item) => ['id' => $item['id'], 'title' => $item['title'] ?? ''], $articles);
            } catch (\Throwable $e) {
                $result['article'] = [];
            }

            try {
                $products = Db::name('product')->where('title', 'like', '%' . $keyword . '%')->limit(10)->select();
                $result['product'] = array_map(fn($item) => ['id' => $item['id'], 'title' => $item['title'] ?? ''], $products);
            } catch (\Throwable $e) {
                $result['product'] = [];
            }

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->success(['article' => [], 'product' => []]);
        }
    }
}