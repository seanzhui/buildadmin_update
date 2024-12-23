/*
 * @Author: juneChen && junechen_0606@163.com
 * @Date: 2022-12-07 09:49:45
 * @LastEditors: juneChen && junechen_0606@163.com
 * @LastEditTime: 2022-12-12 16:58:45
 * @Description:
 *
 * Copyright (c) 2022 by juneChen, All Rights Reserved.
 */
import createAxios from '/@/utils/axios'

export const controllerUrl = '/admin/security.databaseBackup/'

/**
 * 备份数据表
 *
 * @param tables 需要备份的数据表列表
 * @returns
 */
export function backups(tables: string[]) {
    return createAxios(
        {
            url: controllerUrl + 'backups',
            method: 'POST',
            data: {
                tables: tables,
            },
        },
        {
            showSuccessMessage: true,
        }
    )
}

/**
 * 获取备份文件列表
 *
 * @returns
 */
export function restoreList() {
    return createAxios({
        url: controllerUrl + 'restoreList',
        method: 'get',
    })
}

/**
 * 还原数据库
 *
 * @returns
 */
export function restore(time: string) {
    return createAxios(
        {
            url: controllerUrl + 'restore',
            method: 'POST',
            data: {
                time: time,
            },
        },
        {
            showSuccessMessage: true,
        }
    )
}
