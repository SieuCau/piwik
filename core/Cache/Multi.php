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
use Piwik\Piwik;
use Piwik\Version;

/**
 * This class is used to cache data on the filesystem.
 *
 * This cache uses one file for all keys. We will load the cache file only once.
 */
class Multi
{
    private static $content = null;
    private static $isDirty = false;
    private static $ttl = 43200;
    private static $mode;

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
        self::$content = array();
        return true;
    }

    public static function isPopulated()
    {
        return !is_null(self::$content);
    }

    public static function populateCache(Backend $backend, $mode, $eventToSave)
    {
        self::$content = array();
        self::$mode = $mode;

        // TODO also save $backend to flush it in flushAll?

        $content = $backend->doFetch(self::getCacheId($mode));

        if (is_array($content)) {
            self::$content = $content;
        }

        Piwik::addAction($eventToSave, function () use ($backend) {
            Multi::persistCache($backend);
        });
    }

    private static function getCacheId($mode)
    {
        return 'MultiCache-' . str_replace(array('.', '-'), '', Version::VERSION) . '-' . $mode;
    }

    /**
     * @ignore
     */
    public static function persistCache(Backend $backend)
    {
        if (self::$isDirty) {
            $backend->doSave(self::getCacheId(self::$mode), self::$content, self::$ttl);
        }
    }

}
