<?php
/*
 * @Descripttion : QianFox让数字化营销更简单
 * @Author       : QianFox Team
 * @Date         : 2025-09-25 10:46:49
 * @Version      : V1.24
 * @Copyright    : ©2021-现在 
 * @LastEditors  : QianFox Team
 * @LastEditTime : 2025-11-27 16:26:00
 */

namespace app\taglib\fox;

use think\facade\Db;

/**
 * 语言
 */
class TagLanguage extends TagBase
{
    /**
     * 查询数据
     */
    public function getList($param)
    {
        $path = $param['path'];
        $currentstyle = $param['currentstyle'];
        $visit_lang = $this->getLang(); //语言
        $baseurl = request()->domain(); //基本路径
        $includeSelf = $param['self']; // 是否包含当前语言，默认不包含（即默认排除当前语言）

        // 根据参数决定是否排除当前语言
        $langQuery = Db::name('lang')->field("lang,name,pic_flag,native_lang")->where('status', 1);
        if ($includeSelf !== 'true') { // 如果不包含自己，则排除当前语言
            $langQuery->whereNotIn('lang', $visit_lang);
        }

        $langList = $langQuery->select()->toArray();

        $toggle = $param['toggle'];
        if ($toggle == "on") { //开启特殊两门语言切换
            if (sizeof($langList) != 1) {
                echo "抱歉该状态必须为两门语言的时候，才能使用";
                return false;
            }
        }

        $home_lang = xn_cfg("base.home_lang");
        $url = "/index";
        if (!empty($path)) {
            $url = $path;
        } else {
            if (!check_url($baseurl . "/plus/Access/check")) {
                $url = "{$url}";
            }
        }

        $rlist = [];
        foreach ($langList as $key=>$item){
            $itemCurrentStyle = $currentstyle; // 为每个项目保留原始的currentstyle
            if(!($item['lang'] == $visit_lang)){
                $itemCurrentStyle = ""; // 只有在不是当前语言时才清空
            }
            $item['currentstyle'] = $itemCurrentStyle; // 设置该项目的currentstyle

            // 使用一个临时变量保存原始$url用于每个语言重新计算
            $originalUrl = $url;
            if(!($home_lang == $item['lang'])){
                // 如果需要特殊处理home_lang的情况可以在这里补充
            }

            // 每次都基于原始url调用resetIndexUrl
            $finalUrl = resetIndexUrl($originalUrl, $item['lang']);
            $item['url'] = $finalUrl;
            $rlist[] = $item;
        }
        return $rlist;
    }
}