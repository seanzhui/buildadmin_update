<!--
 * @Author: juneChen && junechen_0606@163.com
 * @Date: 2022-12-12 17:43:29
 * @LastEditors: juneChen && junechen_0606@163.com
 * @LastEditTime: 2022-12-13 17:31:57
 * @Description: 
 * 
 * Copyright (c) 2022 by juneChen, All Rights Reserved. 
-->
<template>
    <el-dialog
        class="ba-operate-dialog"
        :model-value="baTable.form.operate ? true : false"
        :title="t('security.databaseBackup.Tab restore database')"
        @close="baTable.toggleForm"
        width="60%"
    >
        <el-table :data="baTable.form.extend!.restore" stripe style="width: 100%" height="500" v-loading="baTable.form.loading">
            <el-table-column prop="backupName" :label="t('security.databaseBackup.Table backup name')" />
            <el-table-column prop="number" :label="t('security.databaseBackup.Table number')" />
            <el-table-column prop="compression" :label="t('security.databaseBackup.Table compression')" />
            <el-table-column prop="dataSize" :label="t('security.databaseBackup.Table data size')" />
            <el-table-column prop="backupTime" :label="t('security.databaseBackup.Table backup time')">
                <template #default="scope">
                    {{ timeFormat(scope.row.backupTime) }}
                </template>
            </el-table-column>
            <el-table-column :label="t('Operate')">
                <template #default="scope">
                    <el-tooltip :content="t('security.databaseBackup.Button restore')" placement="top">
                        <el-button v-blur class="table-header-operate" type="success" @click="restoreClick(scope.$index, scope.row)">
                            <Icon color="#ffffff" name="el-icon-RefreshRight" />
                        </el-button>
                    </el-tooltip>
                </template>
            </el-table-column>
        </el-table>
    </el-dialog>
</template>

<script setup lang="ts">
import { onMounted, inject } from 'vue'
import { useI18n } from 'vue-i18n'
import type BaTable from '/@/utils/baTable'
import { timeFormat } from '/@/utils/common'
import { restore } from '/@/api/backend/security/databaseBackup'

const { t } = useI18n()
const baTable = inject('baTable') as BaTable

onMounted(() => {})

const restoreClick = (index: number, row: TableRow) => {
    baTable.form.loading = true
    restore(row.backupTime).then((res) => {
        baTable.form.loading = false
    })
}
</script>

<script lang="ts"></script>

<style scoped lang="scss"></style>
