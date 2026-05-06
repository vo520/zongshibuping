<?php

namespace app\taglib\fox;

use app\common\util\SitemapUtil;

/**
 * 网站地图
 */
class TagSitemap extends TagBase
{
    /**
     * 网站地图
     */
    public function getList($param)
    {
        $visit_lang = $this->getLang(); //语言
        $type = $param['type'];
        $target = $param['target'];
        if ($type == "html") {
            $path = "/sitemap.html";
        } elseif ($type == "txt") {
            $path = "/sitemap.txt";
        } elseif ($type == "xml") {
            $path = "/sitemap.xml";
        } else {
            $path = "/sitemap.html";
            $type = "html";
        }
        $home_lang = xn_cfg("base.home_lang"); //默认语言
        if ($home_lang == $visit_lang) {
            $filename = $path;
        } else {
            $filename = "/{$visit_lang}{$path}";
            $path = "/{$visit_lang}{$path}";
        }

        // 支持 langtitle  参数
        $title = getLangContentByMark($visit_lang, "sitemap_title")['value'];

        // 如果设置了 langtitle  参数，优先使用 TagLang 方式获取标题
        if (!empty($param['langtitle'])) {
            $langTitleArr = getLangContentByMark($visit_lang, $param['langtitle']);
            if (!empty($langTitleArr) && isset($langTitleArr['value'])) {
                $title = $langTitleArr['value'];
            }
        }
        // 如果设置了 title 参数，优先级最高
        elseif (!empty($param['title'])) {
            $title = $param['title'];
        }

        // 缓存检查：sitemap文件存在且未过期则跳过生成
        $cacheTime = 86400; // 默认缓存24小时，可通过配置调整
        $sitemapCfg = xn_cfg("sitemap");
        if (!empty($sitemapCfg['cache_time'])) {
            $cacheTime = intval($sitemapCfg['cache_time']);
        }

        $rootPath = root_path();
        $fullPath = $rootPath . ltrim($filename, '/');

        if (!file_exists($fullPath) || (time() - filemtime($fullPath)) > $cacheTime) {
            try {
                $baseurl = request()->domain() . "/"; //基本路径
                (new SitemapUtil())->generateSitemap($type, $baseurl, $visit_lang);
            } catch (\Exception $e) {
            }
        }

        $val = '<a href="' . $path . '" target="' . $target . '" class="sitemap">' . $title . '</a>';
        echo $val;
        return false;
    }
}
