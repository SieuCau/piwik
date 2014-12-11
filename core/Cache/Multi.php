<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Cache;

use Piwik\Cache;
use Piwik\Cache\Backend;
use Piwik\Version;

/**
 * This class is used to cache data on the filesystem.
 *
 * This cache uses one file for all keys. We will load the cache file only once.
 */
class Multi
{
    /**
     * @var Backend
     */
    private static $backend = null;
    private static $content = null;
    private static $isDirty = false;

    private $id;

    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * Get the content related to the current cache key. Make sure to call the method {@link has()} to verify whether
     * there is actually any content set under this cache key.
     * @return mixed
     */
    public function get()
    {
        return self::$content[$this->id];
    }

    /**
     * Check whether any content was actually stored for the current cache key.
     * @return bool
     */
    public function has()
    {
        return array_key_exists($this->id, self::$content);
    }

    /**
     * Set (overwrite) any content related to the current set cache key.
     * @param $content
     * @return boolean
     */
    public function set($content)
    {
        self::$content[$this->id] = $content;
        self::$isDirty = true;
        return true;
    }

    /**
     * Deletes a cache entry.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    public function delete()
    {
        if ($this->has()) {
            unset(self::$content[$this->id]);
        }
    }

    /**
     * Flushes all cache entries.
     *
     * @return boolean TRUE if the cache entries were successfully flushed, FALSE otherwise.
     */
    public function flushAll()
    {
        if (!is_null(self::$backend)) {
            self::$backend->doFlush();
        }

        self::$content = array();

        return true;
    }

    public static function isPopulated()
    {
        return !is_null(self::$content);
    }

    public static function populateCache(Backend $backend, $mode)
    {
        self::$content = array();
        self::$backend = $backend;

        $content = $backend->doFetch(self::getCacheId($mode));

        if (is_array($content)) {
            self::$content = $content;
        }
    }

    private static function getCacheId($mode)
    {
        return 'multicache-' . str_replace(array('.', '-'), '', Version::VERSION) . '-' . $mode;
    }

    /**
     * @ignore
     */
    public static function persistCache(Backend $backend, $mode, $ttl)
    {
        if (self::$isDirty) {
            $backend->doSave(self::getCacheId($mode), self::$content, $ttl);
        }
    }

}
