<?php

/**
 * 
 * @Author : Peter
 * @Date : 2023/6/26   19:19
 * @version : V1.08
 * @copyright : ©2026
 * @LastEditTime : 2023/6/26   19:19
 */

namespace app\home\controller;

use app\common\controller\IndexBase;
use think\facade\View;

class Index extends IndexBase
{
    protected $view_suffix; //文件后缀

    protected function initialize()
    {
        parent::initialize();
        $this->view_suffix = config('view.view_suffix');
    }

    // 中文首页
    public function index()
    {
        if (($this->templateType == 2 || $this->templateType == 3) && is_mobile()) { //判断是否手机访问
            $templateMobilePath = $this->templateHtml . "index_m.{$this->view_suffix}";
            if (file_exists($templateMobilePath)) { //判断文件是否存在
                $template = $templateMobilePath;
            } else {
                $template = $this->templateHtml . "index.{$this->view_suffix}";
            }
        } else {
            $template = $this->templateHtml . "index.{$this->view_suffix}";
        }
        $content = View::fetch($template);
        return access_stat_js($content, $this->domainNo);
    }

    // 英文 首页
    //    public function index_en()
    //    {
    //        if(($this->templateType == 2||$this->templateType == 3) && is_mobile()){//判断是否手机访问
    //            $templateMobilePath = $this->templateHtml . "index_en_m.{$this->view_suffix}";
    //            if(file_exists($templateMobilePath)){//判断文件是否存在
    //                $template = $templateMobilePath;
    //            }else{
    //                $template = $this->templateHtml . "index_en.{$this->view_suffix}";
    //            }
    //        }else{
    //            $template = $this->templateHtml . "index_en.{$this->view_suffix}";
    //        }
    //        $content = View::fetch($template);
    //        return access_stat_js($content, $this->domainNo);
    //    }
}