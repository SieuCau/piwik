<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Cache\Backend;

use Doctrine\Common\Cache\PhpFileCache;
use Piwik\Cache\Backend;
use Piwik\Filesystem;

/**
 * This class is used to cache data on the filesystem.
 *
 * This cache creates one file per id. Every time you try to read the value it will load the cache file again.
 */
class File extends PhpFileCache implements Backend
{
    // for testing purposes since tests run on both CLI/FPM (changes in CLI can't invalidate
    // opcache in FPM, so we have to invalidate before reading)
    // TODO ideally we can remove this kinda stuff with dependency injection by using another backend there
    public static $invalidateOpCacheBeforeRead = false;

    /**
     * @var \Callable[]
     */
    private static $onDeleteCallback = array();

    protected $extension = '.php';

    public function doFetch($id)
    {
        if (self::$invalidateOpCacheBeforeRead) {
            $this->invalidateCacheFile($id);
        }

        return parent::doFetch($id);
    }

    public function doContains($id)
    {
        return parent::doContains($id);
    }

    public function doSave($id, $data, $lifeTime = 0)
    {
        if (!is_dir($this->directory)) {
            Filesystem::mkdir($this->directory);
        }

        if (!is_writable($this->directory)) {
            return false;
        }

        $success = parent::doSave($id, $data, $lifeTime);

        $this->invalidateCacheFile($id);

        return $success;
    }

    public function doDelete($id)
    {
        $this->invalidateCacheFile($id);

        $success = parent::doDelete($id);

        $this->invalidateCacheFile($id);

        return $success;
    }

    public function doFlush()
    {
        // TODO we should invalidate all caches also from tracker and whatsoeover
        $self = $this;
        $beforeUnlink = function ($path) use ($self) {
            $self->opCacheInvalidate($path);
        };

        Filesystem::unlinkRecursive($this->directory, $deleteRootToo = false, $beforeUnlink);

        if (!empty(self::$onDeleteCallback)) {
            foreach (self::$onDeleteCallback as $callback) {
                $callback();
            }
        }
    }

    private function invalidateCacheFile($id)
    {
        $filename = $this->getFilename($id);
        $this->opCacheInvalidate($filename);
    }

    /**
     * @param string $id
     *
     * @return string
     */
    protected function getFilename($id)
    {
        $path = $this->directory . DIRECTORY_SEPARATOR;
        $id   = preg_replace('@[\\\/:"*?<>|]+@', '', $id);

        return $path . DIRECTORY_SEPARATOR . $id . $this->extension;
    }

    public function addOnDeleteCallback($onDeleteCallback)
    {
        self::$onDeleteCallback[] = $onDeleteCallback;
    }

    public function opCacheInvalidate($filepath)
    {
        if (is_file($filepath)) {
            if (function_exists('opcache_invalidate')) {
                @opcache_invalidate($filepath, $force = true);
            }
            if (function_exists('apc_delete_file')) {
                @apc_delete_file($filepath);
            }
        }
    }
}
