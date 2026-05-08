<?php

declare(strict_types=1);

namespace app\api\controller;

use think\facade\Db;
use think\facade\Cache;
use app\api\status\Code;

class Template extends ApiBase
{
    private const CACHE_PREFIX = 'api_template_';
    private const DEFAULT_LIMIT = 10;
    private const MAX_LIMIT = 50;

    public function slides(): \think\Response
    {
        try {
            $limit = $this->getValidatedLimit();
            $spaceId = $this->request->param('spaceId/d', 0);

            $cacheKey = self::CACHE_PREFIX . 'slides_' . md5("{$spaceId}:{$limit}");
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $this->success($cached);
            }

            $query = Db::name('slide');
            if ($spaceId > 0) {
                $query->where('advertising_space_id', $spaceId);
            }

            $list = $query->limit($limit)->order('id', 'desc')->select();

            $result = array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'title' => $item['title'] ?? '',
                    'picUrl' => $this->formatUrl($item['pic_url'] ?? ''),
                    'link' => $item['link'] ?? '',
                    'target' => $item['target'] ?? '_self',
                    'spaceId' => $item['advertising_space_id'] ?? 0
                ];
            }, $list);

            Cache::set($cacheKey, $result, $this->cacheExpire);
            return $this->success($result);

        } catch (\Throwable $e) {
            trace('Template::slides error: ' . $e->getMessage(), 'error');
            return $this->error('Failed to fetch slides', Code::SERVER_ERROR);
        }
    }

    public function slideSpaces(): \think\Response
    {
        try {
            $cacheKey = self::CACHE_PREFIX . 'slide_spaces';
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $this->success($cached);
            }

            $list = Db::name('advertising_space')
                ->order('id', 'asc')
                ->select();

            $result = array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name'] ?? '',
                    'width' => $item['width'] ?? '',
                    'height' => $item['height'] ?? '',
                    'description' => $item['description'] ?? ''
                ];
            }, $list);

            Cache::set($cacheKey, $result, $this->cacheExpire);
            return $this->success($result);

        } catch (\Throwable $e) {
            trace('Template::slideSpaces error: ' . $e->getMessage(), 'error');
            return $this->error('Failed to fetch slide spaces', Code::SERVER_ERROR);
        }
    }

    public function links(): \think\Response
    {
        try {
            $limit = $this->getValidatedLimit(20);

            $cacheKey = self::CACHE_PREFIX . 'links_' . $limit;
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $this->success($cached);
            }

            $list = Db::name('link')
                ->limit($limit)
                ->order('id', 'desc')
                ->select();

            $result = array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name'] ?? '',
                    'url' => $item['url'] ?? '',
                    'logo' => $this->formatUrl($item['logo'] ?? ''),
                    'type' => $item['type'] ?? 1
                ];
            }, $list);

            Cache::set($cacheKey, $result, $this->cacheExpire);
            return $this->success($result);

        } catch (\Throwable $e) {
            trace('Template::links error: ' . $e->getMessage(), 'error');
            return $this->error('Failed to fetch links', Code::SERVER_ERROR);
        }
    }

    public function nav(): \think\Response
    {
        try {
            $position = $this->request->param('position', 'header');

            $cacheKey = self::CACHE_PREFIX . 'nav_' . $position;
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $this->success($cached);
            }

            $list = Db::name('nav')
                ->where('position', $position)
                ->order('id', 'asc')
                ->select();

            $result = array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name'] ?? '',
                    'url' => $item['url'] ?? '',
                    'target' => $item['target'] ?? '_self',
                    'position' => $item['position'] ?? 'header'
                ];
            }, $list);

            Cache::set($cacheKey, $result, $this->cacheExpire);
            return $this->success($result);

        } catch (\Throwable $e) {
            trace('Template::nav error: ' . $e->getMessage(), 'error');
            return $this->error('Failed to fetch navigation', Code::SERVER_ERROR);
        }
    }

    public function tagGroups(): \think\Response
    {
        try {
            $cacheKey = self::CACHE_PREFIX . 'tag_groups';
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $this->success($cached);
            }

            $list = Db::name('tag_group')->order('id', 'asc')->select();

            $result = array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name'] ?? '',
                    'description' => $item['description'] ?? ''
                ];
            }, $list);

            Cache::set($cacheKey, $result, $this->cacheExpire);
            return $this->success($result);

        } catch (\Throwable $e) {
            trace('Template::tagGroups error: ' . $e->getMessage(), 'error');
            return $this->error('Failed to fetch tag groups', Code::SERVER_ERROR);
        }
    }

    public function tags(): \think\Response
    {
        try {
            $limit = $this->getValidatedLimit(20);
            $groupId = $this->request->param('groupId/d', 0);

            $cacheKey = self::CACHE_PREFIX . 'tags_' . md5("{$groupId}:{$limit}");
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $this->success($cached);
            }

            $query = Db::name('tag');
            if ($groupId > 0) {
                $query->where('tag_group_id', $groupId);
            }

            $list = $query->limit($limit)->order('id', 'asc')->select();

            $result = array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name'] ?? '',
                    'groupId' => $item['tag_group_id'] ?? 0,
                    'sort' => $item['sort'] ?? 0
                ];
            }, $list);

            Cache::set($cacheKey, $result, $this->cacheExpire);
            return $this->success($result);

        } catch (\Throwable $e) {
            trace('Template::tags error: ' . $e->getMessage(), 'error');
            return $this->error('Failed to fetch tags', Code::SERVER_ERROR);
        }
    }

    public function singles(): \think\Response
    {
        try {
            $cacheKey = self::CACHE_PREFIX . 'singles';
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $this->success($cached);
            }

            $list = Db::name('single')->order('id', 'asc')->select();

            $result = array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'columnId' => $item['column_id'] ?? 0,
                    'title' => $item['title'] ?? '',
                    'description' => $item['description'] ?? ''
                ];
            }, $list);

            Cache::set($cacheKey, $result, $this->cacheExpire);
            return $this->success($result);

        } catch (\Throwable $e) {
            trace('Template::singles error: ' . $e->getMessage(), 'error');
            return $this->error('Failed to fetch single pages', Code::SERVER_ERROR);
        }
    }

    public function single(): \think\Response
    {
        try {
            $columnId = $this->request->param('columnId/d', 0);

            if ($columnId <= 0) {
                return $this->invalidParam('Invalid columnId: must be a positive integer');
            }

            $cacheKey = self::CACHE_PREFIX . 'single_' . $columnId;
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $this->success($cached);
            }

            $single = Db::name('single')->where('column_id', $columnId)->find();

            if (!$single) {
                return $this->notFound('Single page not found');
            }

            $result = [
                'id' => $single['id'],
                'columnId' => $single['column_id'] ?? 0,
                'title' => $single['title'] ?? '',
                'content' => $single['content'] ?? '',
                'seoTitle' => $single['seo_title'] ?? '',
                'seoKeyword' => $single['seo_keyword'] ?? '',
                'seoDesc' => $single['seo_desc'] ?? ''
            ];

            Cache::set($cacheKey, $result, $this->cacheExpire);
            return $this->success($result);

        } catch (\Throwable $e) {
            trace('Template::single error: ' . $e->getMessage(), 'error');
            return $this->error('Failed to fetch single page', Code::SERVER_ERROR);
        }
    }

    public function contact(): \think\Response
    {
        try {
            $cacheKey = self::CACHE_PREFIX . 'contact';
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $this->success($cached);
            }

            $list = Db::name('contact')->order('id', 'asc')->select();

            $result = array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'name' => $item['name'] ?? '',
                    'value' => $item['value'] ?? '',
                    'type' => $item['type'] ?? 1,
                    'sort' => $item['sort'] ?? 0
                ];
            }, $list);

            Cache::set($cacheKey, $result, $this->cacheExpire);
            return $this->success($result);

        } catch (\Throwable $e) {
            trace('Template::contact error: ' . $e->getMessage(), 'error');
            return $this->error('Failed to fetch contact info', Code::SERVER_ERROR);
        }
    }

    public function advertising(): \think\Response
    {
        try {
            $spaceId = $this->request->param('spaceId/d', 0);
            $limit = $this->getValidatedLimit();

            $cacheKey = self::CACHE_PREFIX . 'advertising_' . md5("{$spaceId}:{$limit}");
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $this->success($cached);
            }

            $query = Db::name('slide');
            if ($spaceId > 0) {
                $query->where('advertising_space_id', $spaceId);
            }

            $list = $query->limit($limit)->order('id', 'desc')->select();

            $result = array_map(function ($item) {
                return [
                    'id' => $item['id'],
                    'title' => $item['title'] ?? '',
                    'picUrl' => $this->formatUrl($item['pic_url'] ?? ''),
                    'link' => $item['link'] ?? '',
                    'target' => $item['target'] ?? '_self'
                ];
            }, $list);

            Cache::set($cacheKey, $result, $this->cacheExpire);
            return $this->success($result);

        } catch (\Throwable $e) {
            trace('Template::advertising error: ' . $e->getMessage(), 'error');
            return $this->error('Failed to fetch advertising', Code::SERVER_ERROR);
        }
    }

    public function forms(): \think\Response
    {
        try {
            $formType = $this->request->param('type', '');

            $query = Db::name('form_list');
            if (!empty($formType)) {
                $query->where('form_type', $formType);
            }

            $list = $query->order('id', 'desc')->select();

            $result = array_map(function ($item) {
                $data = $item['data'] ?? [];
                if (is_string($data)) {
                    $data = json_decode($data, true) ?? [];
                }
                return [
                    'id' => $item['id'],
                    'formType' => $item['form_type'] ?? '',
                    'title' => $item['title'] ?? '',
                    'data' => $data,
                    'createTime' => $item['create_time'] ?? ''
                ];
            }, $list);

            return $this->success($result);

        } catch (\Throwable $e) {
            trace('Template::forms error: ' . $e->getMessage(), 'error');
            return $this->error('Failed to fetch forms', Code::SERVER_ERROR);
        }
    }

    private function getValidatedLimit(int $default = null): int
    {
        $default = $default ?? self::DEFAULT_LIMIT;
        $limit = $this->request->param('limit/d', $default);
        return min(self::MAX_LIMIT, max(1, $limit));
    }

    private function formatUrl(string $url): string
    {
        if ($url === '' || $url === null) {
            return '';
        }
        if (preg_match('/^(http:\/\/|https:\/\/)/i', $url)) {
            return $url;
        }
        return '/' . ltrim($url, '/');
    }
}