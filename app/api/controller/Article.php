<?php

declare(strict_types=1);

namespace app\api\controller;

use think\facade\Db;
use app\common\model\Article as ArticleModel;

class Article extends ApiBase
{
    public function list()
    {
        try {
            $page = max(1, intval($this->request->param('page', 1)));
            $size = min(50, max(1, intval($this->request->param('pageSize', 15))));
            $columnId = intval($this->request->param('columnId', 0));

            $url_model = \xn_cfg("seo.url_model");

            $query = Db::name('article');
            if ($columnId > 0) {
                $query->where('column_id', $columnId);
            }

            $total = $query->count();
            $articles = $query->page($page, $size)
                ->order('id', 'desc')
                ->select();

            $result = [];
            foreach ($articles as $item) {
                $img_url = '';
                if (!empty($item['breviary_pic_id'])) {
                    $img = \app\common\model\UploadFiles::field('url')->find($item['breviary_pic_id']);
                    if ($img) {
                        $img_url = $img["url"];
                    }
                }
                if (empty($img_url)) {
                    $img_url = "/static/images/noimage.gif";
                }
                
                $column_name = '';
                if (!empty($item['column_id'])) {
                    $column = \app\common\model\Column::field('name')->find($item['column_id']);
                    if ($column) {
                        $column_name = $column['name'];
                    }
                }

                if ($url_model == 1) {
                    $link = '?s=/article/detail/id/' . $item['id'];
                } else {
                    $link = '/article/detail/' . $item['id'];
                }

                $result[] = [
                    'id' => $item['id'],
                    'column_id' => $item['column_id'],
                    'column' => $column_name,
                    'tags' => $item['tags'] ?? '',
                    'title' => $item['title'] ?? '',
                    'brief_title' => $item['brief_title'] ?? '',
                    'img_url' => $img_url,
                    'click' => $item['click'] ?? 0,
                    'click_count' => $item['click'] ?? 0,
                    'article_field' => $item['article_field'] ?? '',
                    'content' => $item['content'] ?? '',
                    'create_time' => $item['create_time'] ?? '',
                    'release_time' => $item['release_time'] ?? $item['create_time'] ?? '',
                    'keywords' => $item['keywords'] ?? '',
                    'description' => $item['description'] ?? '',
                    'lang' => $item['lang'] ?? '',
                    'link' => $link
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
                return $this->invalidParam('Invalid article ID');
            }

            $article = Db::name('article')->where('id', $id)->find();
            if (!$article) {
                return $this->notFound('Article not found');
            }

            return $this->success([
                'id' => $article['id'],
                'title' => $article['title'] ?? '',
                'content' => $article['content'] ?? '',
                'click' => $article['click'] ?? 0
            ]);
        } catch (\Throwable $e) {
            return $this->success(null);
        }
    }

    public function recommend()
    {
        try {
            $limit = min(20, max(1, intval($this->request->param('limit', 5))));

            $list = Db::name('article')
                ->limit($limit)
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

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->success([]);
        }
    }

    public function hot()
    {
        try {
            $limit = min(20, max(1, intval($this->request->param('limit', 10))));

            $list = Db::name('article')
                ->limit($limit)
                ->order('click', 'desc')
                ->select();

            $result = [];
            foreach ($list as $item) {
                $result[] = [
                    'id' => $item['id'],
                    'title' => $item['title'] ?? '',
                    'click' => $item['click'] ?? 0
                ];
            }

            return $this->success($result);
        } catch (\Throwable $e) {
            return $this->success([]);
        }
    }
}