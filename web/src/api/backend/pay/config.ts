import createAxios from '/@/utils/axios'

export function getConfigKey() {
    return createAxios({
        url: '/admin/pay.Config/getConfigKey',
        method: 'get',
    })
}

export function saveConfig(type: string, data: anyObj) {
    return createAxios(
        {
            url: '/admin/pay.Config/saveConfig',
            method: 'post',
            params: {
                type: type,
            },
            data: data,
        },
        {
            showSuccessMessage: true,
        }
    )
}
