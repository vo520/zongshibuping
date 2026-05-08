<?php

declare(strict_types=1);

namespace app\api\controller;

use app\common\model\Images as ImagesModel;
use app\common\model\Download as DownloadModel;
use app\common\model\Video as VideoModel;

class Images extends ApiBase
{
    public function list()
    {
        [$page, $size] = $this->getPageParams();
        $lang = $this->request->param('lang', '');
        $columnId = intval($this->request->param('columnId', 0));
        $recommend = $this->request->param('recommend', '');

        $query = ImagesModel::where('status', 1);

        if (!empty($lang)) {
            $query->where('lang', $lang);
        }

        if ($columnId > 0) {
            $query->where('column_id', $columnId);
        }

        if ($recommend === '1') {
            $query->whereRaw("FIND_IN_SET('c', article_field)");
        }

        $total = $query->count();
        $list = $query->page($page, $size)
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->select();

        $result = [];
        foreach ($list as $item) {
            $result[] = $this->formatItem($item);
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
    }

    public function detail()
    {
        $id = intval($this->request->param('id', 0));
        $item = ImagesModel::find($id);
        if (!$item || $item['status'] != 1) {
            return $this->error('图片不存在');
        }

        $item->setInc('click', 1);
        return $this->success($this->formatItem($item, true));
    }

    private function formatItem($item, $full = false)
    {
        $result = [
            'id' => $item['id'],
            'columnId' => $item['column_id'],
            'title' => $item['title'],
            'description' => $item['description'],
            'imgUrl' => $item->getData('img_url'),
            'click' => $item['click'],
            'releaseTime' => $item['create_time']
        ];

        if ($full) {
            $result['content'] = $item['content'] ?? '';
            $result['images'] = $item->getData('upload_files');
        }

        return $result;
    }
}

class Download extends ApiBase
{
    public function list()
    {
        [$page, $size] = $this->getPageParams();
        $lang = $this->request->param('lang', '');
        $columnId = intval($this->request->param('columnId', 0));
        $recommend = $this->request->param('recommend', '');

        $query = DownloadModel::where('status', 1);

        if (!empty($lang)) {
            $query->where('lang', $lang);
        }

        if ($columnId > 0) {
            $query->where('column_id', $columnId);
        }

        if ($recommend === '1') {
            $query->whereRaw("FIND_IN_SET('c', article_field)");
        }

        $total = $query->count();
        $list = $query->page($page, $size)
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->select();

        $result = [];
        foreach ($list as $item) {
            $result[] = $this->formatItem($item);
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
    }

    public function detail()
    {
        $id = intval($this->request->param('id', 0));
        $item = DownloadModel::find($id);
        if (!$item || $item['status'] != 1) {
            return $this->error('资源不存在');
        }

        $item->setInc('click', 1);
        return $this->success($this->formatItem($item, true));
    }

    private function formatItem($item, $full = false)
    {
        $result = [
            'id' => $item['id'],
            'columnId' => $item['column_id'],
            'title' => $item['title'],
            'description' => $item['description'],
            'version' => $item['version'] ?? '',
            'fileSize' => $item['file_size'] ?? '',
            'downUrl' => $item['down_url'] ?? '',
            'click' => $item['click'],
            'releaseTime' => $item['create_time'],
            'imgUrl' => $item->getData('img_url')
        ];

        if ($full) {
            $result['content'] = $item['content'] ?? '';
        }

        return $result;
    }
}

class Video extends ApiBase
{
    public function list()
    {
        [$page, $size] = $this->getPageParams();
        $lang = $this->request->param('lang', '');
        $columnId = intval($this->request->param('columnId', 0));
        $recommend = $this->request->param('recommend', '');

        $query = VideoModel::where('status', 1);

        if (!empty($lang)) {
            $query->where('lang', $lang);
        }

        if ($columnId > 0) {
            $query->where('column_id', $columnId);
        }

        if ($recommend === '1') {
            $query->whereRaw("FIND_IN_SET('c', article_field)");
        }

        $total = $query->count();
        $list = $query->page($page, $size)
            ->order('sort', 'desc')
            ->order('id', 'desc')
            ->select();

        $result = [];
        foreach ($list as $item) {
            $result[] = $this->formatItem($item);
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
    }

    public function detail()
    {
        $id = intval($this->request->param('id', 0));
        $item = VideoModel::find($id);
        if (!$item || $item['status'] != 1) {
            return $this->error('视频不存在');
        }

        $item->setInc('click', 1);

        $uploadFiles = $item->getData('upload_files');

        $result = [
            'id' => $item['id'],
            'columnId' => $item['column_id'],
            'title' => $item['title'],
            'description' => $item['description'],
            'content' => $item['content'] ?? '',
            'videoUrl' => $uploadFiles[0]['url'] ?? '',
            'click' => $item['click'],
            'releaseTime' => $item['create_time'],
            'imgUrl' => $item->getData('img_url'),
            'images' => $uploadFiles
        ];

        return $this->success($result);
    }

    private function formatItem($item)
    {
        $uploadFiles = $item->getData('upload_files');
        return [
            'id' => $item['id'],
            'columnId' => $item['column_id'],
            'title' => $item['title'],
            'description' => $item['description'],
            'videoUrl' => $uploadFiles[0]['url'] ?? '',
            'click' => $item['click'],
            'releaseTime' => $item['create_time'],
            'imgUrl' => $item->getData('img_url')
        ];
    }
}