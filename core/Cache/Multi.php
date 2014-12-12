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
class Multi extends Transient
{
    /**
     * @var Backend
     */
    private static $backend = null;
    private static $isDirty = false;
    protected static $content = null;

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

        return parent::flushAll();
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
