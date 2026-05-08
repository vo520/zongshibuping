<?php

declare(strict_types=1);

namespace app\api\controller;

use think\facade\Db;

class Other extends ApiBase
{
    public function slides()
    {
        try {
            $limit = min(20, max(1, intval($this->request->param('limit', 10))));
            $list = Db::name('slide')->limit($limit)->order('id', 'desc')->select();
            $result = [];
            foreach ($list as $item) {
                $result[] = [
                    'id' => $item['id'],
                    'title' => $item['title'] ?? '',
                    'picUrl' => $item['pic_url'] ?? '',
                    'link' => $item['link'] ?? '',
                    'target' => $item['target'] ?? '_self'
                ];
            }
            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->success([]);
        }
    }

    public function links()
    {
        try {
            $limit = min(50, max(1, intval($this->request->param('limit', 20))));
            $list = Db::name('link')->limit($limit)->order('id', 'desc')->select();
            $result = [];
            foreach ($list as $item) {
                $result[] = [
                    'id' => $item['id'],
                    'name' => $item['name'] ?? '',
                    'url' => $item['url'] ?? '',
                    'logo' => $item['logo'] ?? ''
                ];
            }
            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->success([]);
        }
    }

    public function nav()
    {
        try {
            $list = Db::name('nav')->order('id', 'asc')->select();
            $result = [];
            foreach ($list as $item) {
                $result[] = [
                    'id' => $item['id'],
                    'name' => $item['name'] ?? '',
                    'url' => $item['url'] ?? '',
                    'target' => $item['target'] ?? '_self'
                ];
            }
            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->success([]);
        }
    }

    public function config()
    {
        try {
            $key = $this->request->param('key', '');
            if (empty($key)) {
                $configs = Db::name('system')->select();
                $result = [];
                foreach ($configs as $item) {
                    $result[$item['name']] = $item['value'] ?? '';
                }
                return $this->success($result);
            }
            $config = Db::name('system')->where('name', $key)->find();
            if (!$config) {
                return $this->notFound('Config not found');
            }
            return $this->success([$key => $config['value'] ?? '']);
        } catch (\Throwable $e) {
            return $this->success([]);
        }
    }

    public function single()
    {
        try {
            $columnId = intval($this->request->param('columnId', 0));
            if ($columnId <= 0) {
                return $this->invalidParam('Invalid columnId');
            }
            $single = Db::name('single')->where('column_id', $columnId)->find();
            if (!$single) {
                return $this->notFound('Single page not found');
            }
            return $this->success([
                'id' => $single['id'],
                'columnId' => $single['column_id'],
                'title' => $single['title'] ?? '',
                'content' => $single['content'] ?? ''
            ]);
        } catch (\Throwable $e) {
            return $this->success(null);
        }
    }
}