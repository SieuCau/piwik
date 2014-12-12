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

/**
 * This class is used to cache data during one request.
 */
class Transient
{
    protected static $content = array();

    /**
     * Get the content related to the current cache key. Make sure to call the method {@link has()} to verify whether
     * there is actually any content set under this cache key.
     * @return mixed
     */
    public function get($id)
    {
        return self::$content[$id];
    }

    /**
     * Check whether any content was actually stored for the current cache key.
     * @return bool
     */
    public function has($id)
    {
        return array_key_exists($id, self::$content);
    }

    /**
     * Set (overwrite) any content related to the current set cache key.
     * @param $content
     * @return boolean
     */
    public function set($id, $content)
    {
        self::$content[$id] = $content;
        return true;
    }

    /**
     * Deletes a cache entry.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    public function delete($id)
    {
        if ($this->has($id)) {
            unset(self::$content[$id]);
            return true;
        }

        return false;
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

}
