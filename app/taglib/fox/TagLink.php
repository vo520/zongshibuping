<?php

/**
 * 友情链接
 * @Descripttion : PHP 多端跨平台内容管理系统
 * @Author : Peter
 * @Date : 2024/5/9   7:56
 * @version : V1.08
 * @copyright : ©2026
 * @LastEditTime : 2024/5/9   7:56
 */

namespace app\taglib\fox;

class TagLink extends TagBase
{

    public function getList($param, $ob)
    {
        $visit_lang = $this->getLang(); //语言
        $where = [];
        $where[] = ['status', '=', 1];
        $where[] = ['lang', '=', $visit_lang];
        $list = \app\common\model\Link::where($where)->order($ob)->select();
        return $list;
    }
}