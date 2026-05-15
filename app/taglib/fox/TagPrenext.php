<?php
/*
 * 
 * 
 * 
 * 
 * 
 * 
 * @LastEditTime : 2024-05-11 10:50:37
 */

namespace app\taglib\fox;
use app\common\model\Column;
use think\facade\Db;

/**
 * 上一页下一页
 */
class TagPrenext extends TagBase
{

    /**
     * 查询栏目数据
     */
    public function getList($param, $ob="create_time desc")
    {
        $visit_lang = $this->getLang();
        $id = $param["id"];
        $action = \request()->action();
        if(empty($id) || $action != "detail"){
            echo '标签prenext报错：只适用在详情之后。';
            return false;
        }
        $columnModel = strtolower(request()->controller());
        $cm = Db::name($columnModel)->field("column_id")->find($id);
        if(!$cm){
            return ["id"=>-1];
        }
        $column_id = $cm['column_id'];
        $get = $param["get"];
        $disstyle = $param["disstyle"];
        
        $title = $index_name = getLangContentByMark($visit_lang, "no_more")['value'];
        $rdata = ["id"=>-1, "disstyle"=>$disstyle, "title"=> $title];
        
        $isDesc = strstr($ob, 'desc') !== false;
        
        $sortField = trim(explode(' ', $ob)[0]);
        $currentArticle = Db::name($columnModel)->find($id);
        $currentSortValue = $currentArticle[$sortField] ?? null;
        
        if ($get == "pre") {
            $query = Db::name($columnModel)
                ->where("column_id", $column_id)
                ->where('lang', $visit_lang)
                ->where($sortField, $isDesc ? '>' : '<', $currentSortValue);
            
            $query->order($sortField, $isDesc ? 'asc' : 'desc');
            
            $prev = $query->find();
            if ($prev) {
                $rdata = $prev;
            }
        } elseif ($get == "next") {
            $query = Db::name($columnModel)
                ->where("column_id", $column_id)
                ->where('lang', $visit_lang)
                ->where($sortField, $isDesc ? '<' : '>', $currentSortValue);
            
            $query->order($sortField, $isDesc ? 'desc' : 'asc');
            
            $next = $query->find();
            if ($next) {
                $rdata = $next;
            }
        }
        
        $resultList = [];
        array_push($resultList, $rdata);
        return $resultList;
    }

}