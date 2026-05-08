<?php

declare(strict_types=1);

namespace app\api\controller;

use think\facade\Db;

class Download extends ApiBase
{
    public function list()
    {
        try {
            $page = max(1, intval($this->request->param('page', 1)));
            $size = min(50, max(1, intval($this->request->param('pageSize', 15))));

            $total = Db::name('download')->count();
            $list = Db::name('download')
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
                return $this->invalidParam('Invalid download ID');
            }

            $item = Db::name('download')->where('id', $id)->find();
            if (!$item) {
                return $this->notFound('Download not found');
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