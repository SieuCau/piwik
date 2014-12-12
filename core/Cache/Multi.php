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
    private $storage = null;
    private $content = null;
    private $isDirty = false;

    /**
     * Get the content related to the current cache key. Make sure to call the method {@link has()} to verify whether
     * there is actually any content set under this cache key.
     * @return mixed
     */
    public function get($id)
    {
        return $this->content[$id];
    }

    /**
     * Check whether any content was actually stored for the current cache key.
     * @return bool
     */
    public function has($id)
    {
        return array_key_exists($id, $this->content);
    }

    /**
     * Set (overwrite) any content related to the current set cache key.
     * @param $content
     * @return boolean
     */
    public function set($id, $content)
    {
        $this->content[$id] = $content;
        $this->isDirty = true;
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
            unset($this->content[$id]);
        }
    }

    /**
     * Flushes all cache entries.
     *
     * @return boolean TRUE if the cache entries were successfully flushed, FALSE otherwise.
     */
    public function flushAll()
    {
        if (!is_null($this->storage)) {
            $this->storage->doFlush();
        }

        $this->content = array();

        return true;
    }

    public function isPopulated()
    {
        return !is_null($this->content);
    }

    public function populateCache(Backend $storage, $mode)
    {
        $this->content = array();
        $this->storage = $storage;

        $content = $storage->doFetch($this->getCacheId($mode));

        if (is_array($content)) {
            $this->content = $content;
        }
    }

    private function getCacheId($mode)
    {
        return 'multicache-' . str_replace(array('.', '-'), '', Version::VERSION) . '-' . $mode;
    }

    /**
     * @ignore
     */
    public function persistCache(Backend $storage, $mode, $ttl)
    {
        if ($this->isDirty) {
            $storage->doSave($this->getCacheId($mode), $this->content, $ttl);
        }
    }

}
