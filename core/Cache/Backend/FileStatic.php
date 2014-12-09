<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Cache\Backend;
use Piwik\Cache;
use Piwik\Development;
use Piwik\Piwik;
use Piwik\SettingsServer;
use Piwik\Version;

/**
 * This class is used to cache data on the filesystem.
 *
 * This cache uses one file for all keys
 */
class FileStatic implements \Piwik\Cache\Backend
{
    /**
     * @var Cache
     */
    private static $storage = null;
    private static $content = null;
    private static $isDirty = false;
    private static $ttl = 43200;

    /**
     * Initializes the cache.
     * @param string $cacheKey
     */
    public function __construct()
    {
        if (is_null(self::$content)) {
            self::$content = array();
            self::populateCache();
        }
    }

    public function doFetch($id)
    {
        if ($this->doContains($id)) {
            return self::$content[$id];
        }
    }

    public function doContains($id)
    {
        return array_key_exists($id, self::$content);
    }

    public function doDelete($id)
    {
        if ($this->doContains($id)) {
            unset(self::$content[$id]);
            return true;
        }

        return false;
    }

    public function doSave($id, $data, $lifeTime = 0)
    {
        self::$content[$id] = $data;
        self::$isDirty = true;
    }

    public function doFlush()
    {
        self::$content = array();
    }

    private static function populateCache()
    {
        if (Development::isEnabled()) {
            return;
        }

        if (SettingsServer::isTrackerApiRequest()) {
            $eventToPersist = 'Tracker.end';
            $mode           = '-tracker';
        } else {
            $eventToPersist = 'Request.dispatch.end';
            $mode           = '-ui';
        }

        $cache = self::getStorage()->fetch(self::getCacheFilename() . $mode);

        if (is_array($cache)) {
            self::$content = $cache;
        }

        Piwik::addAction($eventToPersist, array(__CLASS__, 'persistCache'));
    }

    private static function getCacheFilename()
    {
        return 'StaticCache-' . str_replace(array('.', '-'), '', Version::VERSION);
    }

    /**
     * @ignore
     */
    public static function persistCache()
    {
        if (self::$isDirty) {
            if (SettingsServer::isTrackerApiRequest()) {
                $mode = '-tracker';
            } else {
                $mode = '-ui';
            }

            self::getStorage()->save(self::getCacheFilename() . $mode, self::$content, self::$ttl);
        }
    }

    /**
     * @ignore
     */
    public static function _reset()
    {
        self::$content = array();
    }

    /**
     * @return Cache
     */
    private static function getStorage()
    {
        if (is_null(self::$storage)) {
            self::$storage = Cache\Factory::buildCache('file', array('directory' => 'tracker'));
        }

        return self::$storage;
    }




}
