<?php

declare(strict_types=1);

namespace app\api\controller;

use think\facade\Db;

class Images extends ApiBase
{
    public function list()
    {
        try {
            $page = max(1, intval($this->request->param('page', 1)));
            $size = min(50, max(1, intval($this->request->param('pageSize', 15))));

            $total = Db::name('images')->count();
            $list = Db::name('images')
                ->page($page, $size)
                ->order('id', 'desc')
                ->select();

            $result = [];
            foreach ($list as $item) {
                $result[] = [
                    'id' => $item['id'],
                    'title' => $item['title'] ?? '',
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
                return $this->invalidParam('Invalid image ID');
            }

            $item = Db::name('images')->where('id', $id)->find();
            if (!$item) {
                return $this->notFound('Image not found');
            }

            return $this->success([
                'id' => $item['id'],
                'title' => $item['title'] ?? '',
                'content' => $item['content'] ?? '',
                'click' => $item['click'] ?? 0
            ]);
        } catch (\Throwable $e) {
            return $this->success(null);
        }
    }
}