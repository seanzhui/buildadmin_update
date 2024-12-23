<?php
/*
 * @Author: juneChen && junechen_0606@163.com
 * @Date: 2022-12-07 13:56:26
 * @LastEditors: juneChen && junechen_0606@163.com
 * @LastEditTime: 2022-12-15 15:49:40
 * @Description: 数据库备份控制器
 * 
 * Copyright (c) 2022 by juneChen, All Rights Reserved. 
 */

namespace app\admin\controller\security;

use app\common\controller\Backend;
use juneChen\dataTableBackup\MysqlBackupOrRestore;

class DatabaseBackup extends Backend
{

    public function initialize(): void
    {
        parent::initialize();
    }

    /**
     * 获取数据表
     *
     * @return void
     * @author juneChen <junechen_0606@163.com>
     */
    public function index(): void
    {

        $mysqlBackupOrRestore = new MysqlBackupOrRestore();
        $data_list            = $mysqlBackupOrRestore->getTableList();

        $this->success('', [
            'list'   => $data_list,
            'remark' => '',
            'total'  => 100
        ]);
    }

    /**
     * 备份数据表
     *
     * @param  [type] $tables 数据表
     * @return void
     * @author juneChen <junechen_0606@163.com>
     */
    public function backups($tables = null): void
    {
        if ($this->request->isPost() && !empty($tables) && is_array($tables)) {
            // 初始化
            $backup = config('databaseBackup.backup');
            if (!is_dir($backup['path'])) {
                mkdir($backup['path'], 0755, true);
            }

            // 读取备份配置
            $config = array(
                'path'     => realpath($backup['path']) . DIRECTORY_SEPARATOR,
                'part'     => $backup['part'],
                'compress' => $backup['compress'],
                'level'    => $backup['level'],
            );

            // 检查是否有正在执行的任务
            $lock = "{$config['path']}backup.lock";
            if (is_file($lock)) {
                $this->error(__('A backup task is in progress'));
            } else {
                // 创建锁文件
                file_put_contents($lock, $this->request->time());
            }

            // 检查备份目录是否可写
            is_writeable($config['path']) || $this->error('The backup directory does not exist or cannot be written');

            // 生成备份文件信息
            $file = array(
                'name' => date('Ymd-His', $this->request->time()),
                'part' => 1,
            );

            // 创建备份文件
            $Database = new MysqlBackupOrRestore($file, $config);
            if (false !== $Database->create()) {
                $start = 0;
                // 备份指定表
                foreach ($tables as $table) {
                    $start = $Database->backup($table, $start);
                    while (0 !== $start) {
                        if (false === $start) { // 出错
                            $this->error(__("Backup error"));
                        }
                        $start = $Database->backup($table, $start[0]);
                    }
                }

                // 备份完成，删除锁定文件
                unlink($lock);

                $this->success();
            } else {
                $this->error(__('Backup file creation failed'));
            }
        } else {
            $this->error(__('Parameter error'));
        }
    }

    /**
     * 备份文件列表
     *
     * @return void
     * @author juneChen <junechen_0606@163.com>
     */
    public function restoreList(): void
    {
        // 列出备份文件列表
        $backup = config('databaseBackup.backup');
        if (!is_dir($backup['path'])) {
            mkdir($backup['path'], 0755, true);
        }
        $path      = realpath($backup['path']);
        $flag      = \FilesystemIterator::KEY_AS_FILENAME;
        $glob      = new \FilesystemIterator($path, $flag);
        $data_list = [];
        foreach ($glob as $name => $file) {
            if (preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql(?:\.gz)?$/', $name)) {
                $name = sscanf($name, '%4s%2s%2s-%2s%2s%2s-%d');

                $date = "{$name[0]}-{$name[1]}-{$name[2]}";
                $time = "{$name[3]}:{$name[4]}:{$name[5]}";
                $part = $name[6];

                if (isset($data_list["{$date}{$time}"])) {
                    $info             = $data_list["{$date}{$time}"];
                    $info['number']   = max($info['number'], $part);
                    $info['dataSize'] = $info['dataSize'] + $file->getSize();
                } else {
                    $info['number']   = $part;
                    $info['dataSize'] = $file->getSize();
                }
                $extension           = strtoupper(pathinfo($file->getFilename(), PATHINFO_EXTENSION));
                $info['compression'] = ($extension === 'SQL') ? '-' : $extension;
                $info['backupTime']  = strtotime("{$date} {$time}");
                $info['backupName']  = "{$name[0]}{$name[1]}{$name[2]}-{$name[3]}{$name[4]}{$name[5]}";

                $data_list["{$date}{$time}"] = $info;
            }
        }

        if (!empty($data_list)) {
            krsort($data_list);
            $data_list = array_values($data_list);
        }

        $this->success('', [
            'list'   => $data_list,
            'remark' => '',
            'total'  => 1
        ]);
    }

    /**
     * 还原数据库
     *
     * @param integer $time 文件时间戳
     * @return void
     * @author juneChen <junechen_0606@163.com>
     */
    public function restore($time = 0): void
    {
        if ($time === 0) $this->error(__('Parameter error'));
        $backup = config('databaseBackup.backup');
        // 初始化
        $name  = date('Ymd-His', $time) . '-*.sql*';
        $path  = realpath($backup['path']) . DIRECTORY_SEPARATOR . $name;
        $files = glob($path);
        $list  = array();
        foreach ($files as $name) {
            $basename        = basename($name);
            $match           = sscanf($basename, '%4s%2s%2s-%2s%2s%2s-%d');
            $gz              = preg_match('/^\d{8,8}-\d{6,6}-\d+\.sql.gz$/', $basename);
            $list[$match[6]] = array($match[6], $name, $gz);
        }
        ksort($list);

        // 检测文件正确性
        $last = end($list);
        if (count($list) === $last[0]) {
            foreach ($list as $item) {
                $config   = [
                    'path'     => realpath($backup['path']) . DIRECTORY_SEPARATOR,
                    'compress' => $item[2]
                ];
                $file     = [
                    "name" => $item[1]
                ];
                $Database = new MysqlBackupOrRestore($file, $config);
                $start    = $Database->restore(0);

                // 循环导入数据
                while (0 !== $start) {
                    if (false === $start) { // 出错
                        $this->error(__('Error restoring data'));
                    }
                    $start = $Database->restore($start[0]);
                }
            }

            $this->success();
        } else {
            $this->error(__("The backup file may be corrupted"));
        }
    }
}
