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

    /**
     * This cache will persist any set data in the configured backend.
     * @param $id
     * @return Cache
     * @throws \DI\NotFoundException
     */
    public static function buildPersistentCache($id = null)
    {
        $cache = StaticContainer::getContainer()->make('Piwik\Cache');

        if (!is_null($id)) {
           $cache->setId($id);
        }

        return $cache;
    }

    /**
     * This cache will not persist any data it contains. It will be only cached during one request. While the persistent
     * cache cannot cache objects this one can cache any kind of data.
     * @param $id
     * @return Cache
     */
    public static function buildTransientCache($id = null)
    {
        $backend = self::getBackend('array', 'objectcache');
        $cache   = StaticContainer::getContainer()->make('Piwik\Cache', array('backend' => $backend));

        if (!is_null($id)) {
           $cache->setId($id);
        }

        return $cache;
    }

    /**
     * Maybe we can find a better name for this cache. This cache saves multiple cache entries under one cache entry.
     * This comes handy for things that we need very often, nearly in every request. Instead of having to read eg.
     * a hundred caches from file we only load one file which contains the hundred keys. Should be used only for things
     * that we need very often (eg list of available plugin widgets classes) and only for cache entries that are not
     * too large to keep loading and parsing the single cache entry fast. This cache is environment aware.
     * If you invalidate a specific cache key it will be only invalidate for the current environment. Eg only tracker
     * cache, or only web cache.
     *
     * @param $id
     * @return Multi
     */
    public static function buildMultiCache($id = null)
    {
        $cache = StaticContainer::getContainer()->make('Piwik\Cache\Multi');

        if (!is_null($id)) {
            $cache->setId($id);
        }

        return $cache;
    }

    public static function flushAll()
    {
        self::buildPersistentCache()->flushAll();
        self::buildTransientCache()->flushAll();
        self::buildMultiCache()->flushAll();
    }

    /**
     * @param $backend
     * @param $namespace
     * @return Backend
     */
    public static function getBackend($backend, $namespace = '')
    {
        $cacheKey = $backend . $namespace;

        if (!array_key_exists($cacheKey, self::$backends)) {
            self::$backends[$cacheKey] = self::buildSpecificBackend($backend);
        }

        return self::$backends[$cacheKey];
    }

    /**
     * @param string $type
     * @return Backend
     */
    private static function buildSpecificBackend($type)
    {
        switch ($type) {
            case 'array':
                return new ArrayCache();

            case 'file':
                return StaticContainer::getContainer()->make('Piwik\Cache\Backend\File');

            case 'chained':

                $options  = self::getBackendOptions($type);
                $backends = array();
                foreach ($options['backends'] as $backendToBuild) {
                    $backends[] = self::buildSpecificBackend($backendToBuild);
                }

                return new Cache\Backend\Chained($backends);

            case 'null':
                return new Cache\Backend\BlackHole();

            default:
                $backend = null;
                $options = self::getBackendOptions($type);

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

    private static function getBackendOptions($backend)
    {
        $key = ucfirst($backend) . 'Cache';
        $options = Config::getInstance()->$key;

        return $options;
    }
}