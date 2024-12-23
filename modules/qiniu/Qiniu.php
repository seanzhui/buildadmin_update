<?php

namespace modules\qiniu;

use ba\Filesystem;
use ba\Version;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use think\App;
use Qiniu\Auth;
use think\facade\Cache;
use think\facade\Event;
use app\common\model\Config;
use app\common\model\Attachment;
use Throwable;
use app\admin\library\module\Server;

class Qiniu
{
    private string $uid = 'qiniu';

    /**
     * @throws Throwable
     */
    public function AppInit(): void
    {
        // 上传配置初始化
        Event::listen('uploadConfigInit', function (App $app) {
            $uploadConfig = get_sys_config('', 'upload');
            if ($uploadConfig['upload_mode'] == 'qiniu' && empty($app->request->upload)) {
                $auth                 = new Auth($uploadConfig['upload_access_key'], $uploadConfig['upload_secret_key']);
                $upToken              = $auth->uploadToken($uploadConfig['upload_bucket']);
                $app->request->upload = [
                    'cdn'    => $uploadConfig['upload_cdn_url'],
                    'mode'   => $uploadConfig['upload_mode'],
                    'url'    => $uploadConfig['upload_url'],
                    'params' => [
                        'token' => $upToken,
                    ]
                ];
            }
        });

        // 附件管理中删除了文件
        Event::listen('AttachmentDel', function (Attachment $attachment) {
            if ($attachment->storage != 'qiniu') {
                return true;
            }
            $uploadConfig = get_sys_config('', 'upload');
            if (!$uploadConfig['upload_access_key'] || !$uploadConfig['upload_secret_key'] || !$uploadConfig['upload_bucket']) {
                return true;
            }
            $auth          = new Auth($uploadConfig['upload_access_key'], $uploadConfig['upload_secret_key']);
            $config        = new \Qiniu\Config();
            $bucketManager = new \Qiniu\Storage\BucketManager($auth, $config);
            $url           = str_replace(full_url(), '', ltrim($attachment->url, '/'));
            $bucketManager->delete($uploadConfig['upload_bucket'], $url);
            return true;
        });
    }

    /**
     * @throws Throwable
     */
    public function enable(): void
    {
        Config::addConfigGroup('upload', '上传配置');
        if (!Config::where('name', 'upload_mode')->value('id')) {
            // 配置数据曾在禁用时被删除
            Server::importSql(root_path() . 'modules' . DIRECTORY_SEPARATOR . $this->uid . DIRECTORY_SEPARATOR);
        }

        // 恢复缓存中的配置数据
        if (Version::compare('v1.1.0', \think\facade\Config::get('buildadmin.version'))) {
            $config = Cache::pull($this->uid . '-module-config');
            if ($config) {
                $config = json_decode($config, true);
                foreach ($config as $item) {
                    Config::where('name', $item['name'])->update([
                        'value' => $item['value']
                    ]);
                }
            }
        }
    }

    /**
     * @throws Throwable
     */
    public function disable(): void
    {
        $config = Config::whereIn('name', ['upload_mode', 'upload_bucket', 'upload_access_key', 'upload_secret_key', 'upload_url', 'upload_cdn_url'])->select();
        // 备份配置到缓存
        if (Version::compare('v1.1.0', \think\facade\Config::get('buildadmin.version'))) {
            if (!$config->isEmpty()) {
                $configData = $config->toArray();
                Cache::set($this->uid . '-module-config', json_encode($configData), 3600);
            }
        }
        foreach ($config as $item) {
            $item->delete();
        }
        Config::removeConfigGroup('upload');
    }

    /**
     * @throws Throwable
     */
    public function update(): void
    {
        // 兼容系统v1.1.2
        // 寻找安装时备份中的baInput.ts文件，如果有，还原到mixins内
        $ebakDir = root_path() . 'modules' . DIRECTORY_SEPARATOR . 'ebak' . DIRECTORY_SEPARATOR;
        $zipFile = $ebakDir . $this->uid . '-install.zip';
        $zipDir  = false;
        if (is_file($zipFile)) {
            try {
                $zipDir = Filesystem::unzip($zipFile);
            } catch (Throwable) {
                // skip
            }
        }
        if ($zipDir) {
            $oldBaInput = Filesystem::fsFit('web\src\components\baInput\components\baUpload.ts');
            @unlink(root_path() . $oldBaInput);
            foreach (
                $iterator = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($zipDir, FilesystemIterator::SKIP_DOTS),
                    RecursiveIteratorIterator::SELF_FIRST
                ) as $item
            ) {
                $ebakFile = Filesystem::fsFit($iterator->getSubPathName());
                if (!$item->isDir() && $ebakFile == $oldBaInput) {
                    copy($item, Filesystem::fsFit(root_path() . 'web/src/components/mixins/baUpload.ts'));
                }
            }
        }
        Filesystem::delDir($zipDir);
    }
}