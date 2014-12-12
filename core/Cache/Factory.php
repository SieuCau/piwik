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
use Piwik\Cache\Backend\ArrayCache;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\Piwik;

class Factory
{

    /**
     * This cache will persist any set data in the configured backend.
     * @return Cache
     * @throws \DI\NotFoundException
     */
    public static function buildPersistentCache()
    {
        return StaticContainer::getContainer()->get('Piwik\Cache');
    }

    /**
     * This cache will not persist any data it contains. It will be only cached during one request. While the persistent
     * cache cannot cache objects this one can cache any kind of data.
     * @return Cache\Transient
     */
    public static function buildTransientCache()
    {
        return StaticContainer::getContainer()->get('Piwik\Cache\Transient');
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
     * @return Multi
     */
    public static function buildMultiCache()
    {
        return StaticContainer::getContainer()->get('Piwik\Cache\Multi');
    }

    public static function flushAll()
    {
        self::buildPersistentCache()->flushAll();
        self::buildTransientCache()->flushAll();
        self::buildMultiCache()->flushAll();
    }

    /**
     * @param $type
     * @return Backend
     */
    public static function buildBackend($type)
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
                    $backends[] = self::buildBackend($backendToBuild);
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