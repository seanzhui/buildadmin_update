import createAxios from '/@/utils/axios'
import { useSiteConfig } from '/@/stores/siteConfig'
import { ElNotification, UploadRawFile } from 'element-plus'
import { randomNum, shortUuid } from '/@/utils/random'
import { fullUrl } from '/@/utils/common'
import { isAdminApp } from '/@/utils/common'
import { AxiosRequestConfig } from 'axios'
import jsSHA from 'jssha'

export const state = () => {
    const siteConfig = useSiteConfig()
    return siteConfig.upload.mode == 'local' ? 'disable' : 'enable'
}

export async function fileUpload(fd: FormData, params: anyObj = {}, config: AxiosRequestConfig = {}): ApiPromise {
    const siteConfig = useSiteConfig()
    const file = fd.get('file') as UploadRawFile
    const sha1 = await getFileSha1(file)
    const fileKey = getSaveName(file, sha1)
    fd.append('key', fileKey)
    for (const key in siteConfig.upload.params) {
        fd.append(key, siteConfig.upload.params[key])
    }
    return new Promise(async (resolve, reject) => {
        createAxios(
            {
                url: siteConfig.upload.url,
                method: 'POST',
                data: fd,
                params: params,
                timeout: 0,
                ...config,
            },
            {
                showCodeMessage: false,
            }
        ).catch((res) => {
            // 七牛的code不会等于1，失败或成功都会被axios封装拦截到此处
            if (res.code && res.error) {
                ElNotification({
                    type: 'error',
                    message: res.error,
                })
                reject({
                    code: 0,
                    data: res,
                    msg: res.error,
                    time: Date.now(),
                })
            } else if (res.code && res.message) {
                reject({
                    code: 0,
                    data: res,
                    msg: res.message,
                    time: Date.now(),
                })
            } else {
                const fileUrl = '/' + res.key
                createAxios({
                    url: isAdminApp() ? '/admin/Qiniu/callback' : '/api/Qiniu/callback',
                    method: 'POST',
                    data: {
                        url: fileUrl,
                        name: file.name,
                        size: file.size,
                        type: file.type,
                        sha1: sha1,
                    },
                })
                resolve({
                    code: 1,
                    data: {
                        file: {
                            full_url: fullUrl(fileUrl),
                            url: fileUrl,
                        },
                    },
                    msg: res.error,
                    time: Date.now(),
                })
            }
        })
    }) as ApiPromise
}

export function getSaveName(file: UploadRawFile, sha1: string) {
    const fileSuffix = file.name.substring(file.name.lastIndexOf('.') + 1)
    const fileName = file.name.substring(0, file.name.lastIndexOf('.'))
    const dateObj = new Date()

    const replaceArr: anyObj = {
        '{topic}': 'default',
        '{year}': dateObj.getFullYear(),
        '{mon}': ('0' + (dateObj.getMonth() + 1)).slice(-2),
        '{day}': dateObj.getDate(),
        '{hour}': dateObj.getHours(),
        '{min}': dateObj.getMinutes(),
        '{sec}': dateObj.getSeconds(),
        '{random}': shortUuid(),
        '{random32}': randomNum(32, 32),
        '{filename}': fileName.substring(0, 15),
        '{suffix}': fileSuffix,
        '{.suffix}': '.' + fileSuffix,
        '{filesha1}': sha1,
    }
    const replaceKeys = Object.keys(replaceArr).join('|')
    const siteConfig = useSiteConfig()

    const savename = siteConfig.upload.savename[0] == '/' ? siteConfig.upload.savename.slice(1) : siteConfig.upload.savename

    return savename.replace(new RegExp(replaceKeys, 'gm'), (match) => {
        return replaceArr[match]
    })
}

async function getFileSha1(file: UploadRawFile) {
    const shaObj = new jsSHA('SHA-1', 'ARRAYBUFFER')
    shaObj.update(await file.arrayBuffer())
    return shaObj.getHash('HEX')
}
