<?php

declare(strict_types=1);

namespace app\api\controller;

use think\facade\Db;

class Column extends ApiBase
{
    public function list()
    {
        try {
            $pid = intval($this->request->param('pid', -1));

            $db = Db::name('column')->order('id', 'asc');

            if ($pid >= 0) {
                $db->where('pid', $pid);
            }

            $list = $db->select();

            $result = [];
            foreach ($list as $item) {
                $result[] = [
                    'id' => $item['id'],
                    'pid' => $item['pid'],
                    'name' => $item['name'] ?? '',
                    'status' => $item['status'] ?? 1,
                    'column_model' => $item['column_model'] ?? '',
                    'nid' => $item['nid'] ?? ''
                ];
            }

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->success([]);
        }
    }

    public function detail()
    {
        try {
            $id = intval($this->request->param('id', 0));

            if ($id <= 0) {
                return $this->invalidParam('Invalid column ID');
            }

            $column = Db::name('column')->where('id', $id)->find();
            if (!$column) {
                return $this->notFound('Column not found');
            }

            return $this->success([
                'id' => $column['id'],
                'pid' => $column['pid'],
                'name' => $column['name'] ?? '',
                'content' => $column['content'] ?? ''
            ]);
        } catch (\Throwable $e) {
            return $this->success(null);
        }
    }

    public function tree()
    {
        try {
            $columns = Db::name('column')->order('id', 'asc')->select()->toArray();
            $tree = $this->buildTree($columns);
            return $this->success($tree);
        } catch (\Throwable $e) {
            return $this->success([]);
        }
    }

    private function buildTree(array $data, int $pid = 0): array
    {
        $tree = [];
        foreach ($data as $item) {
            if (($item['pid'] ?? 0) == $pid) {
                $node = [
                    'id' => $item['id'] ?? 0,
                    'pid' => $item['pid'] ?? 0,
                    'name' => $item['name'] ?? ''
                ];
                $children = $this->buildTree($data, $item['id'] ?? 0);
                if (!empty($children)) {
                    $node['children'] = $children;
                }
                $tree[] = $node;
            }
        }
        return $tree;
    }

    public function breadcrumb()
    {
        try {
            $id = intval($this->request->param('id', 0));

            if ($id <= 0) {
                return $this->invalidParam('Invalid column ID');
            }

            $column = Db::name('column')->where('id', $id)->find();
            if (!$column) {
                return $this->notFound('Column not found');
            }

            $result = [
                ['id' => $column['id'], 'name' => $column['name'] ?? '']
            ];

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->success([]);
        }
    }
}