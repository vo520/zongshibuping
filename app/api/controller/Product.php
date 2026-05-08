<?php

declare(strict_types=1);

namespace app\api\controller;

use think\facade\Db;

class Product extends ApiBase
{
    public function list()
    {
        try {
            $page = max(1, intval($this->request->param('page', 1)));
            $size = min(50, max(1, intval($this->request->param('pageSize', 15))));

            $total = Db::name('product')->count();
            $list = Db::name('product')
                ->page($page, $size)
                ->order('id', 'desc')
                ->select();

            $result = [];
            foreach ($list as $item) {
                $result[] = [
                    'id' => $item['id'],
                    'title' => $item['title'] ?? '',
                    'price' => $item['price'] ?? '',
                    'click' => $item['click'] ?? 0
                ];
            }

            return $this->success([
                'list' => $result,
                'pagination' => [
                    'page' => $page,
                    'pageSize' => $size,
                    'total' => $total,
                    'totalPages' => ceil($total / $size)
                ]
            ]);
        } catch (\Throwable $e) {
            return $this->success(['list' => [], 'pagination' => ['page' => 1, 'pageSize' => $size, 'total' => 0, 'totalPages' => 0]]);
        }
    }

    public function detail()
    {
        try {
            $id = intval($this->request->param('id', 0));
            if ($id <= 0) {
                return $this->invalidParam('Invalid product ID');
            }

            $product = Db::name('product')->where('id', $id)->find();
            if (!$product) {
                return $this->notFound('Product not found');
            }

            return $this->success([
                'id' => $product['id'],
                'title' => $product['title'] ?? '',
                'content' => $product['content'] ?? '',
                'price' => $product['price'] ?? '',
                'click' => $product['click'] ?? 0
            ]);
        } catch (\Throwable $e) {
            return $this->success(null);
        }
    }

    public function recommend()
    {
        try {
            $limit = min(20, max(1, intval($this->request->param('limit', 5))));

            $list = Db::name('product')
                ->limit($limit)
                ->order('id', 'desc')
                ->select();

            $result = [];
            foreach ($list as $item) {
                $result[] = [
                    'id' => $item['id'],
                    'title' => $item['title'] ?? '',
                    'price' => $item['price'] ?? '',
                    'click' => $item['click'] ?? 0
                ];
            }

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->success([]);
        }
    }
}