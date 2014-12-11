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
use Piwik\Cache\Backend\File;
use Piwik\Cache\Backend\ArrayCache;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Piwik;

class Factory
{
    /**
     * @var Backend[]
     */
    private static $backends = array();

    private static $fileCachePath;

    /**
     * @param $id
     * @return Cache
     * @throws \DI\NotFoundException
     */
    public static function buildCache($id)
    {
        $cache = StaticContainer::getContainer()->get('Piwik\Cache');

        if (!empty($id)) {
           $cache->setId($id);
        }

        return $cache;
    }

    /**
     * Maybe we can find a better name for this cache. This cache saves multiple cache entries under one cache entry.
     * This comes handy for things that we need very often, nearly in every request. Instead of having to read eg.
     * a hundred caches from file we only load one file which contains the hundred keys. Should be used only for things
     * that we need very often (eg list of available plugin widgets classes) and only for cache entries that are not
     * too large to keep loading and parsing the single cache entry fast.
     *
     * @return Multi
     */
    public static function buildMultiCache($id)
    {
        $cache = StaticContainer::getContainer()->get('Piwik\Cache\Multi');

        if (!empty($id)) {
            $cache->setId($id);
        }

        return $cache;
    }

    public static function flushAll()
    {
        $backend = StaticContainer::getContainer()->get('Piwik\Cache\Backend');
        $backend->doFlush();
        self::buildMultiCache(null)->flushAll();
    }

    private static function getBackendOptions($backend)
    {
        $key = ucfirst($backend) . 'Cache';
        $options = Config::getInstance()->$key;

        return $options;
    }

    /**
     * @param $backend
     * @param $namespace
     * @return Backend
     */
    public static function getCachedBackend($backend, $namespace)
    {
        $cacheKey = $backend . $namespace;

        if (!array_key_exists($cacheKey, self::$backends)) {
            self::$backends[$cacheKey] = self::buildSpecificBackend($backend, $namespace);
        }

        return self::$backends[$cacheKey];
    }

    /**
     * @param string $type
     * @param string $namespace
     * @return Backend
     */
    private static function buildSpecificBackend($type, $namespace)
    {
        $options = self::getBackendOptions($type);
        $options['namespace'] = $namespace;

        switch ($type) {
            case 'array':
                return new ArrayCache();

            case 'file':
                return self::getFileCache($options);

            case 'chained':
                return self::getChainedCache($options);

            case 'null':
                return new Cache\Backend\BlackHole();

            default:
                $backend = null;

                /**
                 * TODO document this event
                 * @ignore this API is not stable yet
                 */
                Piwik::postEvent('Cache.newBackend', array($type, $options, &$backend));

                if (is_object($backend) && $backend instanceof Backend) {
                    return $backend;
                }

                throw new \InvalidArgumentException("Cache backend $type not valid");
        }
    }

    private static function getCachePath()
    {
        if (is_null(self::$fileCachePath)) {
            $tmp = StaticContainer::getContainer()->get('path.tmp');
            self::$fileCachePath = $tmp . '/cache/';
        }

        return self::$fileCachePath;
    }

    private static function getFileCache($options)
    {
        $path = self::getCachePath();

        if (!empty($options['namespace'])) {
            $path .= $options['namespace'] . '/';
        }

        return new File($path);
    }

    private static function getChainedCache($options)
    {
        $backends = array();
        foreach ($options['backends'] as $backendToBuild) {
            $backends[] = self::getCachedBackend($backendToBuild, $options['namespace']);
        }

        return new Cache\Backend\Chained($backends);
    }

}