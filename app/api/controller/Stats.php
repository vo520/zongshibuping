<?php

declare(strict_types=1);

namespace app\api\controller;

use think\facade\Db;

class Stats extends ApiBase
{
    public function overview()
    {
        try {
            $result = [];

            try { $result['article'] = Db::name('article')->count(); } catch (\Throwable $e) { $result['article'] = 0; }
            try { $result['product'] = Db::name('product')->count(); } catch (\Throwable $e) { $result['product'] = 0; }
            try { $result['images'] = Db::name('images')->count(); } catch (\Throwable $e) { $result['images'] = 0; }
            try { $result['video'] = Db::name('video')->count(); } catch (\Throwable $e) { $result['video'] = 0; }
            try { $result['download'] = Db::name('download')->count(); } catch (\Throwable $e) { $result['download'] = 0; }
            try { $result['column'] = Db::name('column')->count(); } catch (\Throwable $e) { $result['column'] = 0; }

            $result['total'] = $result['article'] + $result['product'] + $result['images'] + $result['video'] + $result['download'];

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->success(['article' => 0, 'product' => 0, 'images' => 0, 'video' => 0, 'download' => 0, 'column' => 0, 'total' => 0]);
        }
    }

    public function popular()
    {
        try {
            $type = $this->request->param('type', 'article');
            $limit = min(50, max(1, intval($this->request->param('limit', 10))));

            $tableMap = ['article' => 'article', 'product' => 'product', 'images' => 'images', 'video' => 'video', 'download' => 'download'];

            if (!isset($tableMap[$type])) {
                return $this->invalidParam('Invalid type');
            }

            $list = Db::name($tableMap[$type])->limit($limit)->order('click', 'desc')->select();

            $result = [];
            foreach ($list as $item) {
                $result[] = ['id' => $item['id'], 'title' => $item['title'] ?? '', 'click' => $item['click'] ?? 0];
            }

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->success([]);
        }
    }
}