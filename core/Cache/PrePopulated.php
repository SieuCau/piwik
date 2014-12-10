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
use Piwik\Development;
use Piwik\Piwik;
use Piwik\SettingsServer;
use Piwik\Version;

/**
 * This class is used to cache data on the filesystem.
 *
 * This cache uses one file for all keys. We will load the cache file only once.
 */
class Prepopulated extends Cache
{
    private static $content = null;
    private static $isDirty = false;
    private static $ttl = 43200;

    /**
     * @var Backend
     */
    private $backend;
    private $id;

    public function __construct(Backend $backend)
    {
        $this->backend = $backend;

        if (is_null(self::$content)) {
            self::$content = array();
            $this->populateCache();
        }
    }

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
        self::_reset();
        return true;
    }

    private function populateCache()
    {
        if (Development::isEnabled()) {
            return;
        }

        // TODO needs to be done in DI
        if (SettingsServer::isTrackerApiRequest()) {
            $eventToPersist = 'Tracker.end';
            $mode           = '-tracker';
        } else {
            $eventToPersist = 'Request.dispatch.end';
            $mode           = '-ui';
        }

        $content = $this->backend->doFetch(self::getCacheFilename() . $mode);

        if (is_array($content)) {
            self::$content = $content;
        }

        $self = $this;
        Piwik::addAction($eventToPersist, function () use ($self) {
            $self->persistCache();
        });
    }

    private static function getCacheFilename()
    {
        return 'StaticCache-' . str_replace(array('.', '-'), '', Version::VERSION);
    }

    /**
     * @ignore
     */
    public function persistCache()
    {
        if (self::$isDirty) {
            if (SettingsServer::isTrackerApiRequest()) {
                // TODO needs to be done in DI
                $mode = '-tracker';
            } else {
                $mode = '-ui';
            }

            $this->backend->doSave(self::getCacheFilename() . $mode, self::$content, self::$ttl);
        }
    }

    /**
     * @ignore
     */
    public static function _reset()
    {
        self::$content = array();
    }

}
