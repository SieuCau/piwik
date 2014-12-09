<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Cache\Backend;

use Exception;
use Piwik\Cache\Backend;
use Piwik\Filesystem;

/**
 * This class is used to cache data on the filesystem.
 *
 * This cache creates one file per id.
 */
class File implements Backend
{

    /**
     * @var string
     */
    private $cachePath;

    // for testing purposes since tests run on both CLI/FPM (changes in CLI can't invalidate
    // opcache in FPM, so we have to invalidate before reading)
    // TODO ideally we can remove this kinda stuff with dependency injection by using another backend there
    public static $invalidateOpCacheBeforeRead = false;

    /**
     * @var \Callable[]
     */
    private static $onDeleteCallback = array();

    /**
     * @param string $directory directory to use
     */
    public function __construct($directory)
    {
        $this->cachePath = $directory;
    }

    /**
     * Fetches an entry from the cache.
     *
     * @param string $id The id of the cache entry to fetch.
     *
     * @return mixed The cached data or FALSE, if no cache entry exists for the given id.
     */
    public function doFetch($id)
    {
        $cache_complete = false;
        $content        = '';
        $expires_on     = false;

        // We are assuming that most of the time cache will exists
        $cacheFilePath = $this->cachePath . $id . '.php';
        if (self::$invalidateOpCacheBeforeRead) {
            $this->opCacheInvalidate($cacheFilePath);
        }

        $ok = @include($cacheFilePath);

        if ($ok && $cache_complete == true) {

            if (empty($expires_on)
                || $expires_on < time()
            ) {
                return false;
            }

            return $content;
        }

        return false;
    }

    /**
     * Tests if an entry exists in the cache.
     *
     * @param string $id The cache id of the entry to check for.
     *
     * @return boolean TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    public function doContains($id)
    {
        return false !== $this->doFetch($id);
    }

    /**
     * Puts data into the cache.
     *
     * @param string $id       The cache id.
     * @param mixed  $data     The cache entry/data.
     * @param int    $lifeTime The cache lifetime.
     *                         If != 0, sets a specific lifetime for this cache entry (0 => infinite lifeTime).
     *
     * @return boolean TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    public function doSave($id, $data, $lifeTime = 0)
    {
        if (!is_dir($this->cachePath)) {
            Filesystem::mkdir($this->cachePath);
        }

        if (!is_writable($this->cachePath)) {
            return false;
        }

        $id = $this->cachePath . $id . '.php';

        if (is_object($data)) {
            throw new \Exception('You cannot use the CacheFile to cache an object, only arrays, strings and numbers.');
        }

        $cache_literal = $this->buildCacheLiteral($data, $lifeTime);

        // Write cache to a temp file, then rename it, overwriting the old cache
        // On *nix systems this should guarantee atomicity
        $tmp_filename = tempnam($this->cachePath, 'tmp_');
        @chmod($tmp_filename, 0640);
        if ($fp = @fopen($tmp_filename, 'wb')) {
            @fwrite($fp, $cache_literal, strlen($cache_literal));
            @fclose($fp);

            if (!@rename($tmp_filename, $id)) {
                // On some systems rename() doesn't overwrite destination
                @unlink($id);
                if (!@rename($tmp_filename, $id)) {
                    // Make sure that no temporary file is left over
                    // if the destination is not writable
                    @unlink($tmp_filename);
                }
            }

            $this->opCacheInvalidate($id);

            return true;
        }

        return false;
    }

    /**
     * Deletes a cache entry.
     *
     * @param string $id The cache id.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    public function doDelete($id)
    {
        $filename = $this->cachePath . $id . '.php';

        if (file_exists($filename)) {
            $this->opCacheInvalidate($filename);
            @unlink($filename);
            return true;
        }

        return false;
    }

    /**
     * Retrieves cached information from the data store.
     *
     * @return array|null An associative array with server's statistics if available, NULL otherwise.
     */
    public function doFlush()
    {
        $self = $this;
        $beforeUnlink = function ($path) use ($self) {
            $self->opCacheInvalidate($path);
        };

        Filesystem::unlinkRecursive($this->cachePath, $deleteRootToo = false, $beforeUnlink);

        if (!empty(self::$onDeleteCallback)) {
            foreach (self::$onDeleteCallback as $callback) {
                $callback();
            }
        }
    }

    private function getExpiresTime($ttl)
    {
        return time() + $ttl;
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

    private function buildCacheLiteral($content, $ttl)
    {
        $cache_literal  = "<" . "?php\n";
        $cache_literal .= "$" . "content   = " . var_export($content, true) . ";\n";
        $cache_literal .= "$" . "expires_on   = " . $this->getExpiresTime($ttl) . ";\n";
        $cache_literal .= "$" . "cache_complete   = true;\n";
        $cache_literal .= "?" . ">";

        return $cache_literal;
    }
}
