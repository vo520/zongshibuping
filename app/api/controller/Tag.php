<?php

declare(strict_types=1);

namespace app\api\controller;

use think\facade\Db;

class Tag extends ApiBase
{
    public function hot()
    {
        try {
            $limit = min(50, max(1, intval($this->request->param('limit', 10))));

            $list = Db::name('tag')->limit($limit)->order('id', 'asc')->select();

            $result = [];
            foreach ($list as $item) {
                $result[] = ['id' => $item['id'], 'name' => $item['name'] ?? ''];
            }

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->success([]);
        }
    }
}