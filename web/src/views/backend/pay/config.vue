<template>
    <div class="default-main">
        <el-row :gutter="20">
            <el-col :span="12">
                <el-collapse class="collapse" v-model="activeNames">
                    <el-collapse-item class="collapse-item" title="支付宝配置" name="ali">
                        <el-form
                            @keyup.enter="onSubmitAli()"
                            ref="aliFormRef"
                            :model="state.ali"
                            :rules="rules.ali"
                            label-position="top"
                            :label-width="200"
                        >
                            <FormItem
                                type="string"
                                label="应用ID(app_id)"
                                prop="app_id"
                                v-model="state.ali.app_id"
                                placeholder="支付宝分配的 app_id"
                            />
                            <FormItem
                                type="textarea"
                                prop="app_secret_cert"
                                label="应用私钥"
                                v-model="state.ali.app_secret_cert"
                                :attr="{
                                    blockHelp:
                                        '在 https://open.alipay.com/develop/manage 《应用详情->开发设置->接口加签方式》中设置，加签方式请选择证书',
                                }"
                                placeholder="请输入应用私钥（字符串或文件绝对路径）"
                            />
                            <FormItem
                                type="string"
                                prop="app_public_cert_path"
                                label="应用公钥证书"
                                v-model="state.ali.app_public_cert_path"
                                :attr="{
                                    blockHelp: '应用审核通过且设置好应用私钥后，可以下载得到需要的其他证书',
                                }"
                                placeholder="请输入应用公钥证书(文件绝对路径)"
                            />
                            <FormItem
                                type="string"
                                prop="alipay_public_cert_path"
                                label="支付宝公钥证书"
                                v-model="state.ali.alipay_public_cert_path"
                                placeholder="请输入支付宝公钥证书(文件绝对路径)"
                            />
                            <FormItem
                                type="string"
                                prop="alipay_root_cert_path"
                                label="支付宝根证书"
                                v-model="state.ali.alipay_root_cert_path"
                                placeholder="请输入支付宝根证书(文件绝对路径)"
                            />
                            <FormItem
                                type="string"
                                prop="notify_url"
                                label="回调通知地址"
                                v-model="state.ali.notify_url"
                                placeholder="请输入回调通知地址"
                            />
                            <FormItem
                                type="string"
                                prop="return_url"
                                label="支付跳转地址"
                                v-model="state.ali.return_url"
                                placeholder="请输入支付跳转地址"
                            />
                            <FormItem
                                type="string"
                                prop="app_auth_token"
                                label="第三方应用授权token"
                                v-model="state.ali.app_auth_token"
                                placeholder="请输入第三方应用授权token"
                            />
                            <FormItem
                                type="radio"
                                label="支付模式"
                                v-model="state.ali.mode"
                                :data="{
                                    content: {
                                        '0': '正常模式',
                                        '1': '沙箱模式',
                                        '2': '服务商模式',
                                    },
                                    childrenAttr: { border: true },
                                }"
                            />
                            <FormItem
                                type="string"
                                label="服务商ID"
                                v-model="state.ali.service_provider_id"
                                placeholder="请输入服务商ID"
                                v-if="state.ali.mode == '2'"
                            />
                            <el-button v-blur :loading="state.ali.loading" @click="onSubmitAli()" type="primary">
                                {{ t('Save') }}
                            </el-button>
                        </el-form>
                    </el-collapse-item>
                    <el-collapse-item class="collapse-item" title="其他配置" name="other">
                        <el-form
                            @keyup.enter="onSubmitOther()"
                            ref="otherFormRef"
                            :model="state.other"
                            :rules="rules.other"
                            label-position="top"
                            :label-width="200"
                        >
                            <FormItem
                                type="radio"
                                label="日志开关"
                                v-model="state.other.enable"
                                :data="{
                                    content: {
                                        '0': '关闭',
                                        '1': '开启',
                                    },
                                    childrenAttr: { border: true },
                                }"
                            />
                            <FormItem type="string" label="日志文件位置" v-model="state.other.file" placeholder="请输入日志文件位置(文件绝对路径)" />
                            <FormItem
                                type="radio"
                                label="日志级别"
                                v-model="state.other.level"
                                :data="{
                                    content: {
                                        debug: '开发环境',
                                        info: '生产环境',
                                    },
                                    childrenAttr: { border: true },
                                }"
                            />
                            <FormItem
                                type="radio"
                                label="日志类型"
                                v-model="state.other.type"
                                :data="{
                                    content: {
                                        single: '单文件存储',
                                        daily: '按天分文件存储',
                                    },
                                    childrenAttr: { border: true },
                                }"
                            />
                            <FormItem
                                v-if="state.other.type == 'daily'"
                                prop="max_file"
                                type="number"
                                label="最大日志文件数"
                                v-model.number="state.other.max_file"
                            />
                            <FormItem prop="timeout" type="number" label="请求超时时间" v-model.number="state.other.timeout" />
                            <FormItem prop="connect_timeout" type="number" label="服务器响应超时时间" v-model.number="state.other.connect_timeout" />
                            <el-button v-blur :loading="state.other.loading" @click="onSubmitOther()" type="primary">
                                {{ t('Save') }}
                            </el-button>
                        </el-form>
                    </el-collapse-item>
                </el-collapse>
            </el-col>
            <el-col :span="12">
                <el-collapse class="collapse" v-model="activeNames">
                    <el-collapse-item class="collapse-item" title="微信配置" name="wx">
                        <el-form
                            @keyup.enter="onSubmitWx()"
                            ref="wxFormRef"
                            :model="state.wx"
                            :rules="rules.wx"
                            label-position="top"
                            :label-width="200"
                        >
                            <FormItem type="string" prop="mch_id" label="微信商户号" v-model="state.wx.mch_id" placeholder="请输入微信商户号" />
                            <FormItem type="string" label="v2商户私钥" v-model="state.wx.mch_secret_key_v2" placeholder="请输入v2商户私钥(选填)" />
                            <FormItem
                                type="string"
                                prop="mch_secret_key"
                                label="v3商户秘钥"
                                v-model="state.wx.mch_secret_key"
                                placeholder="请输入v3商户秘钥"
                            />
                            <FormItem
                                type="textarea"
                                prop="mch_secret_cert"
                                label="商户私钥"
                                v-model="state.wx.mch_secret_cert"
                                :attr="{
                                    blockHelp: '即 API证书 PRIVATE KEY，可在 账户中心->API安全->申请API证书 里获得(apiclient_key.pem)',
                                }"
                                placeholder="请输入商户私钥(字符串或证书文件绝对路径)"
                            />
                            <FormItem
                                type="string"
                                prop="mch_public_cert_path"
                                label="商户公钥证书"
                                v-model="state.wx.mch_public_cert_path"
                                :attr="{
                                    blockHelp: '即 API证书 CERTIFICATE，可在 账户中心->API安全->申请API证书 里获得(apiclient_cert.pem)',
                                }"
                                placeholder="请输入商户公钥证书(文件绝对路径)"
                            />
                            <FormItem
                                type="string"
                                prop="notify_url"
                                label="回调通知地址"
                                v-model="state.wx.notify_url"
                                :attr="{
                                    blockHelp: '不能有参数，如?号，空格等，否则会无法正确回调',
                                }"
                                placeholder="请输入回调通知地址"
                            />
                            <FormItem
                                type="string"
                                label="公众号的AppID"
                                v-model="state.wx.mp_app_id"
                                :attr="{
                                    blockHelp: '可在 mp.weixin.qq.com 设置与开发->基本配置->开发者ID(AppID) 查看',
                                }"
                                placeholder="请输入公众号的AppID"
                            />
                            <FormItem type="string" label="小程序的AppID" v-model="state.wx.mini_app_id" placeholder="请输入小程序的AppID" />
                            <FormItem type="string" label="App的AppID" v-model="state.wx.app_id" placeholder="请输入App的AppID" />
                            <FormItem type="string" label="合单 app_id" v-model="state.wx.combine_app_id" placeholder="请输入合单 app_id" />
                            <FormItem type="string" label="合单商户号" v-model="state.wx.combine_mch_id" placeholder="请输入合单商户号" />
                            <FormItem
                                type="array"
                                label="微信平台公钥证书路径"
                                v-model="state.wx.wechat_public_cert_path"
                                :attr="{
                                    blockHelp: '微信平台公钥证书文件绝对路径',
                                }"
                            />
                            <FormItem
                                type="radio"
                                label="支付模式"
                                v-model="state.wx.mode"
                                :data="{
                                    content: {
                                        '0': '正常模式',
                                        '1': '沙箱模式',
                                        '2': '服务商模式',
                                    },
                                    childrenAttr: { border: true },
                                }"
                            />

                            <FormItem
                                v-if="state.wx.mode == '2'"
                                type="string"
                                label="子商户微信商户号"
                                v-model="state.wx.sub_mp_app_id"
                                placeholder="请输入子商户微信商户号"
                            />
                            <FormItem
                                v-if="state.wx.mode == '2'"
                                type="string"
                                label="子商户的APP的AppID"
                                v-model="state.wx.sub_app_id"
                                placeholder="请输入子商户的APP的AppID"
                            />
                            <FormItem
                                v-if="state.wx.mode == '2'"
                                type="string"
                                label="子商户的小程序的AppID"
                                v-model="state.wx.sub_mini_app_id"
                                placeholder="请输入子商户的小程序的AppID"
                            />
                            <FormItem
                                v-if="state.wx.mode == '2'"
                                type="string"
                                label="子商户的公众号的AppID"
                                v-model="state.wx.sub_mch_id"
                                placeholder="请输入子商户的公众号的AppID"
                            />
                            <el-button v-blur :loading="state.ali.loading" @click="onSubmitWx()" type="primary">
                                {{ t('Save') }}
                            </el-button>
                        </el-form>
                    </el-collapse-item>
                </el-collapse>
            </el-col>
        </el-row>
    </div>
</template>

<script setup lang="ts">
import { reactive, ref } from 'vue'
import { useI18n } from 'vue-i18n'
import { getConfigKey, saveConfig } from '/@/api/backend/pay/config'
import FormItem from '/@/components/formItem/index.vue'
import type { FormInstance, FormItemRule } from 'element-plus'
import { buildValidatorData } from '/@/utils/validate'

const { t } = useI18n()
const wxFormRef = ref<FormInstance>()
const aliFormRef = ref<FormInstance>()
const otherFormRef = ref<FormInstance>()
const activeNames = ref([])
const state = reactive({
    ali: {
        loading: false,
        app_id: '',
        app_secret_cert: '',
        app_public_cert_path: '',
        alipay_public_cert_path: '',
        alipay_root_cert_path: '',
        return_url: '',
        notify_url: '',
        app_auth_token: '',
        service_provider_id: '',
        mode: '',
    },
    wx: {
        loading: false,
        mch_id: '',
        mch_secret_key_v2: '',
        mch_secret_key: '',
        mch_secret_cert: '',
        mch_public_cert_path: '',
        notify_url: '',
        mp_app_id: '',
        mini_app_id: '',
        app_id: '',
        combine_app_id: '',
        combine_mch_id: '',
        sub_mp_app_id: '',
        sub_app_id: '',
        sub_mini_app_id: '',
        sub_mch_id: '',
        wechat_public_cert_path: [],
        mode: '',
    },
    other: {
        loading: false,
        enable: '',
        file: '',
        level: '',
        type: '',
        max_file: '',
        timeout: '',
        connect_timeout: '',
    },
})

const rules: {
    ali: Partial<Record<string, FormItemRule[]>>
    wx: Partial<Record<string, FormItemRule[]>>
    other: Partial<Record<string, FormItemRule[]>>
} = reactive({
    ali: {
        app_id: [buildValidatorData({ name: 'required', title: 'app_id' })],
        app_secret_cert: [buildValidatorData({ name: 'required', title: '应用私钥' })],
        app_public_cert_path: [buildValidatorData({ name: 'required', title: '应用公钥证书' })],
        alipay_public_cert_path: [buildValidatorData({ name: 'required', title: '支付宝公钥证书' })],
        alipay_root_cert_path: [buildValidatorData({ name: 'required', title: '支付宝根证书' })],
    },
    wx: {
        mch_id: [buildValidatorData({ name: 'required', title: '商户号' })],
        mch_secret_key: [buildValidatorData({ name: 'required', title: 'v3商户秘钥' })],
        mch_secret_cert: [buildValidatorData({ name: 'required', title: '商户私钥证书' })],
        mch_public_cert_path: [buildValidatorData({ name: 'required', title: '商户公钥证书' })],
        notify_url: [buildValidatorData({ name: 'required', title: '微信回调url' })],
    },
    other: {
        max_file: [buildValidatorData({ name: 'number', title: '最大日志文件数' })],
        timeout: [buildValidatorData({ name: 'required', title: '请求超时时间' }), buildValidatorData({ name: 'number', title: '请求超时时间' })],
        connect_timeout: [
            buildValidatorData({ name: 'required', title: '服务器响应超时时间' }),
            buildValidatorData({ name: 'number', title: '服务器响应超时时间' }),
        ],
    },
})

const onSubmitAli = () => {
    aliFormRef.value?.validate((valid) => {
        if (!valid) return
        state.ali.loading = true
        saveConfig('alipay', state.ali).finally(() => {
            state.ali.loading = false
        })
    })
}

const onSubmitWx = () => {
    wxFormRef.value?.validate((valid) => {
        if (!valid) return
        state.wx.loading = true
        saveConfig('wechat', state.wx).finally(() => {
            state.wx.loading = false
        })
    })
}

const onSubmitOther = () => {
    otherFormRef.value?.validate((valid) => {
        if (!valid) return
        state.other.loading = true
        saveConfig('other', state.other).finally(() => {
            state.other.loading = false
        })
    })
}

const init = () => {
    state.ali.loading = true
    state.wx.loading = true
    getConfigKey()
        .then((res) => {
            state.ali = res.data.ali
            state.wx = res.data.wx
            state.other = res.data.other
        })
        .finally(() => {
            state.ali.loading = false
            state.wx.loading = false
        })
}
init()
</script>

<style scoped lang="scss">
.collapse {
    width: 100%;
    padding: 20px;
    border-radius: var(--el-border-radius-base);
    background-color: var(--ba-bg-color-overlay);
}
.collapse-item :deep(.el-collapse-item__content) {
    padding-bottom: 15px;
}
@media screen and (max-width: 1024px) {
    .collapse {
        width: 100% !important;
    }
}
</style>
