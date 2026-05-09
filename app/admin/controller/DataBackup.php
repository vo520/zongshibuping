<?php

/**
 * @Descripttion : FOXCMS 是一款高效的 PHP 多端跨平台内容管理系统
 * @Author : Peter
 * @Date : 2023/6/26   14:43
 * @version : V1.08
 * @copyright : ©2026
 * @LastEditTime : 2023/6/26   14:43
 */

namespace app\admin\controller;

use app\admin\util\Basckup;
use app\admin\util\ModelMg;
use app\common\controller\AdminBase;
use think\facade\Db;
use think\facade\View;

class DataBackup extends AdminBase
{
    private $filterTable = ['fox_admin_log'];

    // 验证表名是否合法
    private function validateTableName(string $tableName): bool
    {
        if (empty($tableName) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            return false;
        }
        return true;
    }

    // 检查表是否存在
    private function tableExists(string $tableName): bool
    {
        try {
            $result = Db::query("SHOW TABLES LIKE '{$tableName}'");
            return !empty($result);
        } catch (\Exception $e) {
            return false;
        }
    }

    // 验证备份路径是否在允许范围内
    private function validateBackupPath(string $path): bool
    {
        $basePath = app()->getRootPath() . 'data';
        $realBasePath = realpath($basePath);
        if ($realBasePath === false) {
            return false;
        }
        
        $realBasePath = rtrim($realBasePath, '/\\') . DIRECTORY_SEPARATOR;
        
        if (preg_match('/\.\.[\/\\\\]/', $path)) {
            return false;
        }
        
        if (preg_match('/\.\.%2[f5c]/i', $path)) {
            return false;
        }
        
        $realPath = realpath($path);
        if ($realPath !== false) {
            $realPath = rtrim($realPath, '/\\') . DIRECTORY_SEPARATOR;
            return strpos($realPath, $realBasePath) === 0;
        }
        
        return true;
    }

    public function index($page = 1, $pageSize = 1000)
    {
        if ($this->request->isAjax()) {
            $sql = 'SHOW TABLE STATUS';
            $list = Db::query($sql);
            $rlist = [];
            foreach ($list as $table) {
                $tableName = $table['Name'];
                if (!in_array($tableName, $this->filterTable)) {
                    $count = Db::table($tableName)->count();
                    $comment = $table['Comment'];
                    array_push($rlist, ['name' => $tableName, 'count' => $count, 'comment' => $comment]);
                }
            }
            try {
                $bfday = date("Y-m-d", strtotime("-7 day"));
                \app\common\model\DataBackup::where([['create_time', '<', $bfday]])->delete();
            } catch (\Exception $e) {
            }
            $this->success('查询成功', '', $rlist);
        }
        //SQL命令行
        $safeExcute = \app\common\model\Safe::where(['dict_label' => 'sql_execute'])->find();
        $sql_execute_value = 0;
        if ($safeExcute) {
            $sql_execute_value = $safeExcute['dict_value'];
        }
        View::assign("sql_execute_value", $sql_execute_value); //SQL状态
        return view('index');
    }

    // 备份
    public function backup()
    {
        $param = $this->request->param();
        $tableName = $param['tableName'];
        if (empty($tableName)) {
            $this->error("备份失败,表名为空");
        }
        // 验证表名合法性
        if (!$this->validateTableName($tableName)) {
            $this->error("备份失败,表名不合法");
        }
        // 检查表是否存在
        if (!$this->tableExists($tableName)) {
            $this->error("备份失败,表不存在");
        }
        $random_num = func_random_num(18);
        $fileName = $tableName . "-" . $random_num . ".sql";
        $content =  (new Basckup())->getbackupTable($tableName); //模型表与数据内容
        $time = date('Y-m-d');
        $path = app()->getRootPath() . 'data' . DIRECTORY_SEPARATOR . 'backupdata' . DIRECTORY_SEPARATOR . $time . DIRECTORY_SEPARATOR;
        if (!tp_mkdir($path)) {
            $this->error("创建文件夹失败");
        }
        $fpath = write($path, $fileName, $content); //存入模型字段数据
        if (empty($fpath)) {
            $this->error("备份失败");
        }
        $backup_file = $tableName . ".sql";

        $dataBackup = new \app\common\model\DataBackup();

        $cDataBackup = $dataBackup->where(['backup_file' => $time])->find();
        $pid = 0;
        if ($cDataBackup) {
            $pid = $cDataBackup->id;
        } else {
            $dataBackup->save(['pid' => 0, 'backup_file' => $time, 'backup_file_path' => $path,  'table_name' => $time]);
            if (empty($dataBackup->id)) {
                $this->error("操作失败");
            }
            $pid = $dataBackup->id;
        }
        $r = (new \app\common\model\DataBackup())->save(['pid' => $pid, 'backup_file' => $backup_file, 'backup_file_path' => $fpath, 'random_num' => $random_num, 'table_name' => $tableName]);
        if ($r) {
            xn_add_admin_log("备份数据", "backup"); //添加日志
            $this->success('操作成功', "", $fpath);
        } else {
            $this->error("操作失败");
        }
    }

    // 备份全部文件
    public function backupAll()
    {
        $param = $this->request->param();
        $tableList = json_decode($param['tableList']);
        if (array_key_exists("tableList", $param)) {
            if (sizeof($tableList) <= 0) {
                $this->error("备份失败,参数错误");
            }
        }
        $saveAll = [];
        $time = date('Y-m-d');
        $path = app()->getRootPath() . 'data' . DIRECTORY_SEPARATOR . 'backupdata' . DIRECTORY_SEPARATOR . $time . DIRECTORY_SEPARATOR;
        if (!tp_mkdir($path)) {
            $this->error("创建文件夹失败");
        }
        $dataBackup = new \app\common\model\DataBackup();
        $cDataBackup = $dataBackup->where(['backup_file' => $time])->find();
        $pid = 0;
        if ($cDataBackup) {
            $pid = $cDataBackup->id;
        } else {
            $dataBackup->save(['pid' => 0, 'backup_file' => $time, 'backup_file_path' => $path,  'table_name' => $time]);
            if (empty($dataBackup->id)) {
                $this->error("操作失败");
            }
            $pid = $dataBackup->id;
        }

        foreach ($tableList as $tableName) {
            // 验证表名合法性
            if (!$this->validateTableName($tableName)) {
                $this->error("备份失败,表名不合法: " . $tableName);
            }
            // 检查表是否存在
            if (!$this->tableExists($tableName)) {
                $this->error("备份失败,表不存在: " . $tableName);
            }
            $random_num = func_random_num(18);
            $fileName = $tableName . "-" . $random_num . ".sql";
            $content =  (new Basckup())->backupTable($tableName); //模型表与数据内容
            $fpath = write($path, $fileName, $content); //存入模型字段数据
            if (empty($fpath)) {
                $this->error("备份失败");
            }
            $backup_file = $tableName . ".sql";
            array_push($saveAll, ['pid' => $pid, 'backup_file' => $backup_file, 'backup_file_path' => $fpath, 'random_num' => $random_num, 'table_name' => $tableName]);
        }
        $rSaveData = (new \app\common\model\DataBackup())->saveAll($saveAll);
        if (sizeof($rSaveData) <= 0) {
            $this->error('操作失败');
        }
        xn_add_admin_log("批量备份数据", "backup"); //添加日志
        $this->success('操作成功', "", $rSaveData);
    }

    // 数据还原初始化 备份系列
    public function backupIndex()
    {
        $fArr = array();
        $activepath = '/backupdata';
        $basePath =  app()->getRootPath() . 'data' . $activepath;
        $arr_file = getDirFile($basePath, $activepath, $fArr);
        $list = [];
        foreach ($arr_file as $ar) {
            if ($ar['filetype'] != "dir2" && $ar['filemine'] == "dir") {
                $rdata['id'] = $ar['filepath'];
                $rdata['backup_file'] = $ar['filename'];
                $creationTime = filectime(replaceSymbol($basePath . $ar['filepath']));
                $rdata['create_time'] =  date("Y-m-d H:i:s", $creationTime);
                array_push($list, $rdata);
            }
        }
        $this->success("查询成功", "", $list);
    }

    // 恢复备份系列
    public function restoreSerie()
    {
        $id = $this->request->param("id");
        if (empty($id)) {
            $this->error("恢复失败,参数错误");
        }
        $basePath =  app()->getRootPath() . 'data';
        $sqlFolderPath = replaceSymbol($basePath . $id); //执行模板sql文件
        // 验证路径安全性
        if (!$this->validateBackupPath($sqlFolderPath)) {
            $this->error("恢复失败,路径不合法");
        }
        if (!is_dir($sqlFolderPath)) {
            $this->error("恢复失败,没找到对应恢复数据");
        }
        $files = dirFile($sqlFolderPath);
        if (sizeof($files) > 0) {
            foreach ($files as $file) {
                try {
                    $sqlPath = $sqlFolderPath . DIRECTORY_SEPARATOR . $file;
                    $sqlContent = @file_get_contents($sqlPath);
                    $sqlFormat  = (new ModelMg())->sql_split($sqlContent, env('database.prefix', 'fox_'));
                    $counts = count($sqlFormat);
                    $createSuccess = false;
                    for ($i = 0; $i < $counts; $i++) {
                        $sql = trim($sqlFormat[$i]);
                        if (trim($sql) == '')
                            continue;
                        if (str_starts_with($sql, '--') || str_starts_with($sql, '/*'))
                            continue;
                        
                        // 只允许执行安全SQL：DROP TABLE、CREATE TABLE、INSERT
                        $isDrop = stristr($sql, 'DROP TABLE');
                        $isCreate = stristr($sql, 'CREATE TABLE');
                        $isInsert = stristr($sql, 'INSERT INTO');
                        if (!$isDrop && !$isCreate && !$isInsert) {
                            continue;
                        }
                        
                        // DROP TABLE：先执行
                        if ($isDrop) {
                            try { Db::execute($sql); } catch (\Exception $e) {}
                            continue;
                        }
                        
                        // CREATE TABLE：必须成功
                        if ($isCreate) {
                            try {
                                Db::execute($sql);
                                $createSuccess = true;
                            } catch (\Exception $e) {
                                $createSuccess = false;
                            }
                            continue;
                        }
                        
                        // INSERT：CREATE TABLE成功后才执行
                        if ($isInsert && $createSuccess) {
                            try { Db::execute($sql); } catch (\Exception $e) {}
                        }
                    }
                } catch (\Exception $e) {
                    $this->error("恢复失败,执行sql错误");
                }
            }
        } else {
            $this->error("恢复失败");
        }
        $this->success("恢复成功");
    }

    // 删除备份系列
    public function delRestoreSerie()
    {
        $id = $this->request->param("id");
        if (empty($id)) {
            $this->error("删除失败,参数错误");
        }
        $basePath =  app()->getRootPath() . 'data';
        $sqlFolderPath = replaceSymbol($basePath . $id); //执行模板sql文件
        // 验证路径安全性
        if (!$this->validateBackupPath($sqlFolderPath)) {
            $this->error("删除失败,路径不合法");
        }
        if (!is_dir($sqlFolderPath)) {
            $this->error("恢复失败,没找到对应恢复数据");
        }
        delDir($sqlFolderPath); //删除子文件数据
        $this->success("删除成功");
    }

    // 删除批量备份系列
    public function delRestoreSeries()
    {
        $param = $this->request->param();
        $idList = json_decode($param['idList']);
        if (array_key_exists("idList", $param)) {
            if (sizeof($idList) <= 0) {
                $this->error("删除失败,参数错误");
            }
        }
        $basePath =  app()->getRootPath() . 'data';
        foreach ($idList as $id) {
            $sqlFolderPath = replaceSymbol($basePath . $id); //执行模板sql文件
            // 验证路径安全性
            if (!$this->validateBackupPath($sqlFolderPath)) {
                continue;
            }
            if (!is_dir($sqlFolderPath)) {
                continue;
            }
            delDir($sqlFolderPath);
        }
        $this->success("删除成功");
    }

    // 点击备份目录文件
    public function tempFile()
    {
        $param = $this->request->param();
        if ($this->request->isAjax()) {
            $fArr = array();
            $activepath = $param['pid'];
            $basePath =  app()->getRootPath() . 'data';
            $basePath1 = replaceSymbol($basePath . $activepath);
            // 验证路径安全性
            if (!$this->validateBackupPath($basePath1)) {
                $this->error("查询失败,路径不合法");
            }
            $arr_file = getDirFile($basePath1, $activepath, $fArr);
            $list = [];
            foreach ($arr_file as $ar) {
                if ($ar['filemine'] == "file") {
                    $rdata['id'] = $ar['filepath'];
                    $rdata['filesize'] = $ar['filesize'];
                    $rdata['backup_file'] = $ar['filename'];
                    $creationTime = filectime(replaceSymbol($basePath . $ar['filepath']));
                    $rdata['create_time'] =  date("Y-m-d H:i:s", $creationTime);
                    array_push($list, $rdata);
                }
            }
            $this->success("查询成功", "", $list);
        }
        View::assign("pid", $param['id']);
        return view();
    }

    // 删除恢复文件
    public function delRestoreFile()
    {
        $id = $this->request->param("id");
        if (empty($id)) {
            $this->error("删除失败,参数错误");
        }
        $sqlPath = app()->getRootPath() . 'data' . $id;
        $sqlPath = replaceSymbol($sqlPath);
        // 验证路径安全性
        if (!$this->validateBackupPath($sqlPath)) {
            $this->error("删除失败,路径不合法");
        }
        if (!file_exists($sqlPath)) {
            $this->error("恢复失败,参数错误");
        }
        $r = @unlink($sqlPath);
        if ($r) {
            $this->success("删除成功");
        }
        $this->error("删除失败");
    }

    // 批量删除恢复文件
    public function delRestoreFiles()
    {
        $param = $this->request->param();
        $idList = json_decode($param['idList']);
        if (array_key_exists("idList", $param)) {
            if (sizeof($idList) <= 0) {
                $this->error("删除失败,参数错误");
            }
        }
        $errorCount = 0;
        $basepath = app()->getRootPath() . 'data';
        foreach ($idList as $id) {
            $sqlPath = replaceSymbol($basepath . $id);
            // 验证路径安全性
            if (!$this->validateBackupPath($sqlPath)) {
                continue;
            }
            if (!file_exists($sqlPath)) {
                continue;
            }
            $r = @unlink($sqlPath);
            if (!$r) {
                $errorCount++;
            }
        }
        if ($errorCount > 0) {
            $this->error("删除失败");
        }
        $this->success("删除成功");
    }

    // 恢复数据
    public function restore()
    {
        $id = $this->request->param("id");
        if (empty($id)) {
            $this->error("恢复失败,参数错误");
        }
        $sqlPath = app()->getRootPath() . 'data' . $id;
        $sqlPath = replaceSymbol($sqlPath);
        // 验证路径安全性
        if (!$this->validateBackupPath($sqlPath)) {
            $this->error("恢复失败,路径不合法");
        }
        if (!file_exists($sqlPath)) {
            $this->error("恢复失败,参数错误");
        }
        $sqlContent = @file_get_contents($sqlPath);
        if (empty($sqlContent)) {
            $this->error("恢复失败,文件内容为空");
        }
        $sqlFormat  = (new ModelMg())->sql_split($sqlContent, env('database.prefix', 'fox_'));
        // 执行SQL语句
        $successCount = 0;
        $failCount = 0;
        $createSuccess = false;
        $counts = count($sqlFormat);
        for ($i = 0; $i < $counts; $i++) {
            $sql = trim($sqlFormat[$i]);
            if (trim($sql) == '')
                continue;
            
            // 跳过注释
            if (str_starts_with($sql, '--') || str_starts_with($sql, '/*'))
                continue;
            
            // DROP TABLE：先执行，不计入成功/失败
            if (stristr($sql, 'DROP TABLE')) {
                try {
                    Db::execute($sql);
                } catch (\Exception $e) {
                    // DROP TABLE 失败可以忽略
                }
                continue;
            }
            
            // CREATE TABLE：必须成功
            if (stristr($sql, 'CREATE TABLE')) {
                try {
                    Db::execute($sql);
                    $successCount++;
                    $createSuccess = true;
                } catch (\Exception $e) {
                    $failCount++;
                    $createSuccess = false;
                }
                continue;
            }
            
            // 如果 CREATE TABLE 还没成功，跳过 INSERT
            if (!$createSuccess) {
                continue;
            }
            
            // INSERT：执行
            try {
                Db::execute($sql);
                $successCount++;
            } catch (\Exception $e) {
                $failCount++;
            }
        }
        
        if ($successCount > 0) {
            $msg = "恢复完成，成功: {$successCount} 条";
            if ($failCount > 0) {
                $msg .= "，失败: {$failCount} 条(数据兼容性问题，已跳过)";
            }
            $this->success($msg);
        } else {
            $this->error("恢复失败，所有SQL执行均失败");
        }
    }

    // 批量恢复数据
    public function restores()
    {
        $param = $this->request->param();
        $idList = json_decode($param['idList']);
        if (array_key_exists("idList", $param)) {
            if (sizeof($idList) <= 0) {
                $this->error("删除失败,参数错误");
            }
        }
        $basepath = app()->getRootPath() . 'data';
        $successCount = 0;
        $failCount = 0;
        foreach ($idList as $id) {
            $sqlPath = $basepath . $id;
            $sqlPath = replaceSymbol($sqlPath);
            // 验证路径安全性
            if (!$this->validateBackupPath($sqlPath)) {
                $failCount++;
                continue;
            }
            if (!file_exists($sqlPath)) {
                $failCount++;
                continue;
            }
            $sqlContent = @file_get_contents($sqlPath);
            if (empty($sqlContent)) {
                $failCount++;
                continue;
            }
            $sqlFormat  = (new ModelMg())->sql_split($sqlContent, env('database.prefix', 'fox_'));
            // 执行SQL语句
            $createSuccess = false;
            $counts = count($sqlFormat);
            for ($i = 0; $i < $counts; $i++) {
                $sql = trim($sqlFormat[$i]);
                if (trim($sql) == '')
                    continue;
                
                // 跳过注释
                if (str_starts_with($sql, '--') || str_starts_with($sql, '/*'))
                    continue;
                
                // DROP TABLE：先执行，不计入成功/失败
                if (stristr($sql, 'DROP TABLE')) {
                    try {
                        Db::execute($sql);
                    } catch (\Exception $e) {
                        // DROP TABLE 失败可以忽略
                    }
                    continue;
                }
                
                // CREATE TABLE：必须成功
                if (stristr($sql, 'CREATE TABLE')) {
                    try {
                        Db::execute($sql);
                        $successCount++;
                        $createSuccess = true;
                    } catch (\Exception $e) {
                        $failCount++;
                        $createSuccess = false;
                    }
                    continue;
                }
                
                // 如果 CREATE TABLE 还没成功，跳过 INSERT
                if (!$createSuccess) {
                    continue;
                }
                
                // INSERT：执行
                try {
                    Db::execute($sql);
                    $successCount++;
                } catch (\Exception $e) {
                    $failCount++;
                }
            }
        }
        if ($successCount > 0) {
            $msg = "恢复完成，成功: {$successCount} 条";
            if ($failCount > 0) {
                $msg .= "，失败: {$failCount} 条(数据兼容性问题，已跳过)";
            }
            $this->success($msg);
        } else {
            $this->error("恢复失败，所有SQL执行均失败");
        }
    }
}