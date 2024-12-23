<?php
/*
 * @Author: juneChen && junechen_0606@163.com
 * @Date: 2022-12-06 16:04:14
 * @LastEditors: juneChen && junechen_0606@163.com
 * @LastEditTime: 2022-12-15 15:48:54
 * @Description: 备份Mysql数据表到文件
 * 
 * Copyright (c) 2022 by juneChen, All Rights Reserved. 
 */

namespace juneChen\dataTableBackup;

use think\facade\Db;

class MysqlBackupOrRestore
{
    /**
     * 文件指针
     * @var resource
     */
    private $fp;

    /**
     * 备份文件信息 part - 卷号，name - 文件名
     * @var array
     */
    private array $file = [
        'name' => "Backup",
        'part' => 1
    ];

    /**
     * 当前打开文件大小
     * @var integer
     */
    private int $size = 0;

    /**
     * 备份配置
     * @var array
     */
    private array $config = [
        'path'     => "data/",
        //备份卷大小 默认 20M
        'part'     => 20971520,
        // 备份文件是否启用压缩
        "compress" => 1,
        // 备份文件压缩级别
        "level"    => 9,
    ];

    /**
     * 数据库配置
     * @var string
     */
    private static string $database_config = "mysql";

    /**
     * 数据库备份构造方法
     * @param array $file   备份或还原的文件信息
     * @param array $config 备份配置信息
     */
    public function __construct(array $file = [], array $config = [])
    {
        if (!empty($file)) {
            $this->file = $file;
        }

        if (!empty($config)) {
            $this->config = $config;
        }

        if (isset($config['database_config'])) {
            self::$database_config = $config['database_config'];
        }
    }

    /**
     * 获取数据库所有表信息列表
     *
     * @return array
     * @author juneChen <junechen_0606@163.com>
     */
    public function getTableList(): array
    {
        $tableList = Db::connect(self::$database_config)->query("SHOW TABLE STATUS");
        if (empty($tableList)) {
            return [];
        }

        return array_map(function ($val) {
            return [
                "tableName"   => $val['Name'],
                "lineNumber"  => $val['Rows'],
                "size"        => $this->format_bytes($val['Data_length']),
                "bredundancy" => $this->format_bytes($val['Data_free']),
                "comment"     => $val['Comment'],
            ];
        }, $tableList);
    }

    /**
     * 写入初始数据
     * @return bool|int
     */
    public function create(): bool|int
    {
        $database_config = Db::connect(self::$database_config)->getConfig();
        $sql             = "-- -----------------------------\n";
        $sql             .= "-- MySQL Data Transfer\n";
        $sql             .= "--\n";
        $sql             .= "-- Host     : " . $database_config['hostname'] . "\n";
        $sql             .= "-- Port     : " . $database_config['hostport'] . "\n";
        $sql             .= "-- Database : " . $database_config['database'] . "\n";
        $sql             .= "--\n";
        $sql             .= "-- Part : #{$this->file['part']}\n";
        $sql             .= "-- Date : " . date("Y-m-d H:i:s") . "\n";
        $sql             .= "-- -----------------------------\n\n";
        $sql             .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";
        return $this->write($sql);
    }

    /**
     * 备份表结构和数据
     * @param string  $table 表名
     * @param integer $start 起始行数
     * @return array|bool|int  false - 备份失败
     */
    public function backup(string $table = '', int $start = 0): int|bool|array
    {
        // 备份表结构
        if (0 == $start) {
            $result = Db::connect(self::$database_config)->query("SHOW CREATE TABLE `$table`");
            $result = array_map('array_change_key_case', $result);

            $sql = "\n";
            $sql .= "-- -----------------------------\n";
            $sql .= "-- Table structure for `$table`\n";
            $sql .= "-- -----------------------------\n";
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= trim($result[0]['create table']) . ";\n\n";
            if (false === $this->write($sql)) {
                return false;
            }
        }

        // 数据总数
        $result = Db::connect(self::$database_config)->query("SELECT COUNT(*) AS count FROM `$table`");
        $count  = $result['0']['count'];

        //备份表数据
        if ($count) {
            // 写入数据注释
            if (0 == $start) {
                $sql = "-- -----------------------------\n";
                $sql .= "-- Records of `{$table}`\n";
                $sql .= "-- -----------------------------\n";
                $this->write($sql);
            }

            // 备份数据记录
            $result = Db::connect(self::$database_config)->query("SELECT * FROM `$table` LIMIT $start, 1000");
            foreach ($result as $row) {
                $row = array_map(function ($val) {
                    // 解决数据值是 null 时，addslashes报错
                    if (is_null($val)) {
                        $val = 'NULL';
                    } elseif (is_string($val)) {
                        $val = "'" . addslashes($val) . "'";
                    }
                    return $val;
                }, $row);

                $sql = str_replace('%DATA%', implode(", ", $row), "INSERT INTO `$table` VALUES (%DATA%);\n");

                if (false === $this->write($sql)) {
                    return false;
                }
            }

            //还有更多数据
            if ($count > $start + 1000) {
                return [$start + 1000, $count];
            }
        }

        // 备份下一表
        return 0;
    }

    /**
     * 恢复数据
     *
     * @param integer $start 起始位置
     * @return array|bool|int
     * @author juneChen <junechen_0606@163.com>
     */
    public function restore(int $start = 0): int|bool|array
    {

        if ($this->config['compress']) {
            $gz   = gzopen($this->file['name'], 'r');
            $size = 0;
        } else {
            $size = filesize($this->file['name']);
            $gz   = fopen($this->file['name'], 'r');
        }

        $sql = '';
        if ($start) {
            $this->config['compress'] ? gzseek($gz, $start) : fseek($gz, $start);
        }

        for ($i = 0; $i < 1000; $i++) {
            $sql .= $this->config['compress'] ? gzgets($gz) : fgets($gz);
            if (preg_match('/.*;$/', trim($sql))) {
                if (false !== Db::connect(self::$database_config)->execute($sql)) {
                    $start += strlen($sql);
                } else {
                    return false;
                }
                $sql = '';
            } elseif ($this->config['compress'] ? gzeof($gz) : feof($gz)) {
                return 0;
            }
        }

        return [$start, $size];
    }

    /**
     * 析构方法，用于关闭文件资源
     */
    public function __destruct()
    {
        if ($this->fp) $this->config['compress'] ? @gzclose($this->fp) : @fclose($this->fp);
    }

    /**
     * 打开一个卷，用于写入数据
     *
     * @param float $size 写入数据的大小
     * @return void
     * @author juneChen <junechen_0606@163.com>
     */
    private function open(float $size = 0): void
    {
        if ($this->fp) {
            $this->size += $size;
            if ($this->size > $this->config['part']) {
                $this->config['compress'] ? @gzclose($this->fp) : @fclose($this->fp);
                $this->fp = null;
                $this->file['part']++;
                $this->create();
            }
        } else {
            $backup_path = $this->config['path'];
            $filename    = "{$backup_path}{$this->file['name']}-{$this->file['part']}.sql";
            if ($this->config['compress']) {
                $filename = "{$filename}.gz";
                $this->fp = @gzopen($filename, "a{$this->config['level']}");
            } else {
                $this->fp = @fopen($filename, 'a');
            }
            $this->size = filesize($filename) + $size;
        }
    }

    /**
     * 写入SQL语句
     * @param string $sql 要写入的SQL语句
     * @return bool|int
     */
    private function write(string $sql = ''): bool|int
    {
        $size = strlen($sql);

        // 由于压缩原因，无法计算出压缩后的长度，这里假设压缩率为50%，
        // 一般情况压缩率都会高于50%；
        $size = $this->config['compress'] ? $size / 2 : $size;

        $this->open($size);
        return $this->config['compress'] ? @gzwrite($this->fp, $sql) : @fwrite($this->fp, $sql);
    }

    /**
     * 格式化字节大小
     *
     * @param int    $size      字节数
     * @param string $delimiter 数字和单位分隔符
     * @return string            格式化后的带单位的大小
     * @author juneChen <junechen_0606@163.com>
     */
    private function format_bytes(int $size, string $delimiter = ''): string
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB', 'PB');
        for ($i = 0; $size >= 1024 && $i < 5; $i++) $size /= 1024;
        return round($size, 2) . $delimiter . $units[$i];
    }
}
