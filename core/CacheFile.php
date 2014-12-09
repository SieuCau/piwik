<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik;

use Exception;
use Piwik\Cache\Backend;
use Piwik\Container\StaticContainer;

/**
 * This class is used to cache data on the filesystem.
 *
 * It is for example used by the Tracker process to cache various settings and websites attributes in tmp/cache/tracker/*
 *
 * @deprecated
 */
class CacheFile
{
    /**
     * Minimum enforced TTL in seconds
     */
    const MINIMUM_TTL = 60;

    /**
     * @var
     */
    private $fileBackend;

    /**
     * @param string $directory directory to use
     * @param int $timeToLiveInSeconds TTL
     */
    public function __construct($directory, $timeToLiveInSeconds = 300)
    {
        $directory = StaticContainer::getContainer()->get('path.tmp') . '/cache/' . $directory . '/';

        $this->fileBackend = new Backend\File($directory);

        if ($timeToLiveInSeconds < self::MINIMUM_TTL) {
            $timeToLiveInSeconds = self::MINIMUM_TTL;
        }

        $this->ttl = $timeToLiveInSeconds;
    }

    /**
     * Function to fetch a cache entry
     *
     * @param string $id The cache entry ID
     * @return array|bool  False on error, or array the cache content
     */
    public function get($id)
    {
        if (empty($id)) {
            return false;
        }

        $id = $this->cleanupId($id);

        return $this->fileBackend->doFetch($id);
    }

    /**
     * A function to store content a cache entry.
     *
     * @param string $id The cache entry ID
     * @param array $content The cache content
     * @throws \Exception
     * @return bool  True if the entry was succesfully stored
     */
    public function set($id, $content)
    {
        if (empty($id)) {
            return false;
        }

        $id = $this->cleanupId($id);

        return $this->fileBackend->doSave($id, $content, $this->ttl);
    }

    /**
     * A function to delete a single cache entry
     *
     * @param string $id The cache entry ID
     * @return bool  True if the entry was succesfully deleted
     */
    public function delete($id)
    {
        if (empty($id)) {
            return false;
        }

        $id = $this->cleanupId($id);

        return $this->fileBackend->doDelete($id);
    }

    public function addOnDeleteCallback($onDeleteCallback)
    {
        $this->fileBackend->addOnDeleteCallback($onDeleteCallback);
    }

    /**
     * A function to delete all cache entries in the directory
     */
    public function deleteAll()
    {
        return $this->fileBackend->doFlush();
    }


    protected function cleanupId($id)
    {
        if (!Filesystem::isValidFilename($id)) {
            throw new Exception("Invalid cache ID request $id");
        }

        return $id;
    }

}
