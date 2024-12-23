<?php
/*
 * @Author: juneChen && junechen_0606@163.com
 * @Date: 2022-12-07 10:26:34
 * @LastEditors: juneChen && junechen_0606@163.com
 * @LastEditTime: 2022-12-12 17:15:26
 * @Description: 数据库备份配置
 * 
 * Copyright (c) 2022 by juneChen, All Rights Reserved. 
 */

return [
    'backup' => [
        'path'     => "backup/",
        //备份卷大小 默认 20M
        'part'     => 20971520,
        // 备份文件是否启用压缩
        "compress" => 1,
        // 备份文件压缩级别
        "level"    => 9,
    ]
];
