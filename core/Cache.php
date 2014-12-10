<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik;

use Piwik\Cache\Backend;

class Cache
{
    private $backend;
    private $id;

    public function __construct(Backend $backend)
    {
        $this->backend = $backend;
    }

    public function setId($id)
    {
        $this->checkId($id);
        $this->id = $this->completeKey($id);
    }

    private function checkId($id)
    {
        if (empty($id)) {
            throw new \Exception('Empty cache ID given');
        }

        if (!Filesystem::isValidFilename($id)) {
            throw new \Exception("Invalid cache ID request $id");
        }
    }

    /**
     * Prefixes the passed id with the configured namespace value.
     *
     * @param string $id The id to namespace.
     *
     * @return string The namespaced id.
     */
    protected function completeKey($id)
    {
        return sprintf('piwikcache_%s', $id);
    }

    /**
     * Fetches an entry from the cache.
     *
     * @return mixed The cached data or FALSE, if no cache entry exists for the given id.
     */
    public function get()
    {
        return $this->backend->doFetch($this->id);
    }

    /**
     * Tests if an entry exists in the cache.
     *
     * @return boolean TRUE if a cache entry exists for the given cache id, FALSE otherwise.
     */
    public function has()
    {
        return $this->backend->doContains($this->id);
    }

    /**
     * Puts data into the cache.
     *
     * @param mixed  $data     The cache entry/data.
     * @param int    $lifeTime The cache lifetime.
     *                         If != 0, sets a specific lifetime for this cache entry (0 => infinite lifeTime).
     *
     * @return boolean TRUE if the entry was successfully stored in the cache, FALSE otherwise.
     */
    public function set($data, $lifeTime = 0)
    {
        return $this->backend->doSave($this->id, $data, $lifeTime);
    }

    /**
     * Deletes a cache entry.
     *
     * @return boolean TRUE if the cache entry was successfully deleted, FALSE otherwise.
     */
    public function delete()
    {
        return $this->backend->doDelete($this->id);
    }

    /**
     * Flushes all cache entries.
     *
     * @return boolean TRUE if the cache entries were successfully flushed, FALSE otherwise.
     */
    public function flushAll()
    {
        return $this->backend->doFlush();
    }
}
