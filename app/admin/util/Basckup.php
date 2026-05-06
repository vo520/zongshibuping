<?php

/**
 * @Descripttion : FOXCMS 是一款高效的 PHP 多端跨平台内容管理系统
 * @Author : FoxCMS Team
 * @Date : 2023/6/26   15:49
 * @version : V1.08
 * @copyright : ©2021-现在 贵州黔狐科技股份有限公司 版权所有
 * @LastEditTime : 2023/6/26   15:49
 */

namespace app\admin\util;

use think\facade\Db;

// 备份数据
class Basckup
{
    // 验证表名是否合法
    private function validateTableName(string $tableName): bool
    {
        if (empty($tableName) || !preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $tableName)) {
            return false;
        }
        return true;
    }

    // 生成表结构与数据
    public function backupTable($tableName, $isData = true, $start = 0)
    {
        if (!$this->validateTableName($tableName)) {
            throw new \Exception('表名不合法');
        }
        $result = Db::query("SHOW CREATE TABLE `{$tableName}`");
        $sql = "\nDROP TABLE IF EXISTS `{$tableName}`;\n";
        $sql .= trim($result[0]['Create Table']) . ";\n\n";

        if ($isData) {
            $result = Db::query("SELECT COUNT(*) AS count FROM `{$tableName}`");
            $count = $result['0']['count'];
            if ($count > 0) {
                $result = Db::query("SELECT * FROM `{$tableName}` LIMIT {$start}, {$count}");
                $fieldArr = \think\facade\Db::table("{$tableName}")->getFields();
                foreach ($result as $row) {
                    $row = array_map('addslashes', $row);
                    $fields = array_keys($row);
                    $field = implode("`, `", $fields);
                    $valueArr = [];
                    foreach ($fields as $fieldName) {
                        $fieldVal = $this->getFieldVal($fieldArr, $fieldName, $row[$fieldName]);
                        $valueArr[] = $fieldVal;
                    }
                    $value = str_replace(array("\r", "\n"), array('\r', '\n'), implode(",", $valueArr));
                    $sql .= "INSERT INTO `{$tableName}`(`" . $field . "`) VALUES (" . $value . ");\n";
                }
            }
        }
        return $sql;
    }

    // 生成表数据
    public function backupTableData($tableName, $where = "1=1", $start = 0)
    {
        if (!$this->validateTableName($tableName)) {
            throw new \Exception('表名不合法');
        }
        $sql = "\n";
        $result = Db::query("SELECT COUNT(*) AS count FROM `{$tableName}` where {$where}");
        $count = $result['0']['count'];
        if ($count > 0) {
            $result = Db::query("SELECT * FROM `{$tableName}` where {$where} LIMIT {$start}, {$count}");
            $fieldArr = \think\facade\Db::table("{$tableName}")->getFields();
            foreach ($result as $row) {
                $row = array_map('addslashes', $row);
                $fields = array_keys($row);
                $field = implode("`, `", $fields);
                $valueArr = [];
                foreach ($fields as $fieldName) {
                    $fieldVal = $this->getFieldVal($fieldArr, $fieldName, $row[$fieldName]);
                    $valueArr[] = $fieldVal;
                }
                $value = str_replace(array("\r", "\n"), array('\r', '\n'), implode(",", $valueArr));
                $sql .= "INSERT INTO `{$tableName}`(`" . $field . "`) VALUES (" . $value . ");\n";
            }
        }
        return $sql;
    }

    // 生成表数据
    public function getBackupTableData($tableName, $where = "1=1", $start = 0)
    {
        if (!$this->validateTableName($tableName)) {
            throw new \Exception('表名不合法');
        }
        $sql = "\n";
        $result = Db::query("SELECT COUNT(*) AS count FROM `{$tableName}` where {$where}");
        $count = $result['0']['count'];
        if ($count > 0) {
            $result = Db::query("SELECT * FROM `{$tableName}` where {$where} LIMIT {$start}, {$count}");
            $fieldArr = \think\facade\Db::table("{$tableName}")->getFields();
            foreach ($result as $row) {
                $row = array_map('addslashes', $row);
                $fields = array_keys($row);
                $fieldStr = implode("`, `", $fields);
                $valueArr = [];
                foreach ($fields as $fieldName) {
                    $fieldVal = $this->getFieldVal($fieldArr, $fieldName, $row[$fieldName]);
                    $valueArr[] = $fieldVal;
                }
                $val = implode(",", $valueArr);
                $value = str_replace(array("\r", "\n"), array('\r', '\n'), $val);
                $sql .= "INSERT INTO `{$tableName}`(`" . $fieldStr . "`) VALUES (" . $value . ");\n";
            }
        }
        return $sql;
    }

    // 获取表数据及结构
    public function getbackupTable($tableName, $isData = true, $start = 0)
    {
        if (!$this->validateTableName($tableName)) {
            throw new \Exception('表名不合法');
        }
        $result = Db::query("SHOW CREATE TABLE `{$tableName}`");
        $structSql = "\nDROP TABLE IF EXISTS `{$tableName}`;\n";
        $structSql .= trim($result[0]['Create Table']) . ";\n\n";
        if (!$isData) {
            $structSql = preg_replace("/ENGINE=InnoDB AUTO_INCREMENT=(\d+)/i", "ENGINE=InnoDB AUTO_INCREMENT=1", $structSql);
            $structSql = preg_replace("/ENGINE=MyISAM AUTO_INCREMENT=(\d+)/i", "ENGINE=InnoDB AUTO_INCREMENT=1", $structSql);
        }
        $rdata = [$structSql];
        if ($isData) {
            $dataql = "\n";
            $result = Db::query("SELECT COUNT(*) AS count FROM `{$tableName}`");
            $count = $result['0']['count'];
            if ($count > 0) {
                $result = Db::query("SELECT * FROM `{$tableName}` LIMIT {$start}, {$count}");
                $fieldArr = \think\facade\Db::table("{$tableName}")->getFields();
                foreach ($result as $row) {
                    $row = array_map('addslashes', $row);
                    $fields = array_keys($row);
                    $fieldStr = implode("`, `", $fields);
                    $valueArr = [];
                    foreach ($fields as $fieldName) {
                        $fieldVal = $this->getFieldVal($fieldArr, $fieldName, $row[$fieldName]);
                        $valueArr[] = $fieldVal;
                    }
                    $val = implode(",", $valueArr);
                    $value = str_replace(array("\r", "\n"), array('\r', '\n'), $val);
                    $dataql .= "INSERT INTO `{$tableName}`(`" . $fieldStr . "`) VALUES (" . $value . ");\n";
                }
            }
            $rdata[] = $dataql;
        }
        return $rdata;
    }

    // 根据字段类型处理值
    private function getFieldVal($fields, $field, $value)
    {
        $fieldArr = $fields[$field];
        $type = strtolower($fieldArr["type"]);
        
        // 整型
        if (str_starts_with($type, "int") || str_starts_with($type, "tinyint") || str_starts_with($type, "bigint") || str_starts_with($type, "smallint")) {
            return ($value !== null && $value !== '') ? $value : "null";
        }
        // 浮点型
        if (str_starts_with($type, "decimal") || str_starts_with($type, "float") || str_starts_with($type, "double") || str_starts_with($type, "real")) {
            return ($value !== null && $value !== '') ? $value : "null";
        }
        // 字符串类型
        if (str_starts_with($type, "varchar") || str_starts_with($type, "char") || str_starts_with($type, "text")) {
            return ($value != '0' && empty($value)) ? "''" : "'" . $value . "'";
        }
        // 日期时间类型
        if (str_starts_with($type, "datetime") || str_starts_with($type, "timestamp") || str_starts_with($type, "date") || str_starts_with($type, "time")) {
            return (empty($value) || $value == "0000-00-00 00:00:00" || $value == "0000-00-00") ? "null" : "'" . $value . "'";
        }
        // 其他类型
        return ($value !== null && $value !== '') ? "'" . $value . "'" : "null";
    }
}
