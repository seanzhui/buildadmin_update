<template>
    <div class="default-main ba-table-box">
        <!-- 表格顶部菜单 -->
        <TableHeader :buttons="['refresh']">
            <el-popconfirm
                @confirm="onBackupsAction"
                :confirm-button-text="t('security.databaseBackup.Button backups')"
                :cancel-button-text="t('Cancel')"
                :title="t('security.databaseBackup.Are you sure to back up the selected tabales')"
                :disabled="baTable.table.selection!.length > 0 ? false:true"
            >
                <template #reference>
                    <div class="mlr-12">
                        <el-tooltip :content="t('security.databaseBackup.TableHeader backups the database')" placement="top">
                            <el-button
                                :disabled="baTable.table.selection!.length > 0 ? false:true"
                                v-blur
                                class="table-header-operate"
                                type="primary"
                                :loading="state.backupsLoading"
                            >
                                <Icon color="#ffffff" name="el-icon-Coin" />
                                <span class="table-header-operate-text">{{ t('security.databaseBackup.Button backups') }}</span>
                            </el-button>
                        </el-tooltip>
                    </div>
                </template>
            </el-popconfirm>
            <el-tooltip :content="t('security.databaseBackup.TableHeader restoring the database')" placement="top">
                <el-button v-blur class="table-header-operate" type="success" @click="restoringButtonClick">
                    <Icon color="#ffffff" name="el-icon-RefreshRight" />
                    <span class="table-header-operate-text">{{ t('security.databaseBackup.Button restore') }}</span>
                </el-button>
            </el-tooltip>
        </TableHeader>

        <Table :pagination="state.pagination" height="calc(100vh - 175px)" ref="tableRef" />

        <!-- 备份文件列表 -->
        <Restore />
    </div>
</template>

<script setup lang="ts">
import { provide, ref, reactive, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import Restore from './restore.vue'
import Table from '/@/components/table/index.vue'
import TableHeader from '/@/components/table/header/index.vue'
import baTableClass from '/@/utils/baTable'
import { baTableApi } from '/@/api/common'
import { controllerUrl, backups, restoreList } from '/@/api/backend/security/databaseBackup'

const { t } = useI18n()
const tableRef = ref()

const state: {
    pagination: boolean
    backupsLoading: boolean
} = reactive({
    pagination: false,
    backupsLoading: false,
})

let optButtons: OptButton[] = [
    {
        render: 'confirmButton',
        name: 'backups',
        title: 'security.databaseBackup.Button backups',
        text: '',
        type: 'primary',
        icon: 'el-icon-Coin',
        class: 'table-row-info',
        disabledTip: false,
        popconfirm: {
            title: t('security.databaseBackup.Are you sure to back up the selected tabales'),
        },
        click: (row: TableRow) => {
            onBackups([row[baTable.table.pk!]])
        },
    },
]
optButtons = optButtons.concat()

const baTable = new baTableClass(
    new baTableApi(controllerUrl),
    {
        column: [
            { type: 'selection', align: 'center', operator: false },
            {
                label: t('security.databaseBackup.Table table name'),
                prop: 'tableName',
                operator: false,
            },
            {
                label: t('security.databaseBackup.Table line number'),
                prop: 'lineNumber',
                operator: false,
            },
            { label: t('security.databaseBackup.Table size'), prop: 'size', operator: false },
            { label: t('security.databaseBackup.Table bredundancy'), prop: 'bredundancy', operator: false },
            {
                label: t('security.databaseBackup.Table comment'),
                prop: 'comment',
                operator: false,
            },
            {
                label: t('Operate'),
                align: 'center',
                width: 120,
                render: 'buttons',
                buttons: optButtons,
                operator: false,
            },
        ],
        dblClickNotEditColumn: [undefined, 'status'],
    },
    {
        defaultItems: {
            status: '1',
        },
    }
)

// 实例化表格后，将 baTable 的实例提供给上下文
provide('baTable', baTable)

onMounted(() => {
    baTable.table.ref = tableRef.value
    baTable.table.pk = 'tableName'
    baTable.mount()
    baTable.getIndex()
})

const onBackups = (tables: string[]) => {
    if (state.backupsLoading) {
        return false
    }
    state.backupsLoading = true
    baTable.table.ref!.getRef().clearSelection()
    backups(tables).then(() => {
        state.backupsLoading = false
    })
}

const onBackupsAction = () => {
    onBackups(baTable.getSelectionIds())
}

const restoringButtonClick = () => {
    baTable.form.extend!['restore'] = []
    baTable.form.operate = 'restore'
    baTable.form.loading = true
    restoreList().then((res) => {
        baTable.form.extend!['restore'] = res.data.list
        baTable.form.loading = false
    })
}
</script>

<style scoped lang="scss">
.table-header-operate {
    margin-left: 12px;
}
.default-main {
    margin-bottom: 0;
}
</style>
