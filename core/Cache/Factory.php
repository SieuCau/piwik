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

    public static function buildCache($id)
    {
        $backend = self::buildBackend();

        $cache = new Cache($backend);

        if (!empty($id)) {
           $cache->setId($id);
        }

        return $cache;
    }

    public static function buildBackend()
    {
        $options = self::getCacheOptions();
        // TODO namespace should be set in DI

        return self::getCachedBackend($options['backend'], $options);
    }

    /**
     * Maybe we can find a better name for this cache. This cache saves multiple cache entries under one cache entry.
     * This comes handy for things that we need very often, nearly in every request. Instead of having to read eg.
     * a hundred caches from file we only load one file which contains the hundred keys. Should be used only for things
     * that we need very often (eg list of available plugin widgets classes) and only for cache entries that are not
     * too large to keep loading and parsing the single cache entry fast.
     */
    public static function buildMultiCache($id)
    {
        $backend = self::buildBackend();
        $cache   = new Multi($backend);

        if (!empty($id)) {
            $cache->setId($id);
        }

        return $cache;
    }

    public static function flushAll()
    {
        self::buildBackend()->doFlush();
        self::buildMultiCache(null)->flushAll();
    }

    private static function getCacheOptions()
    {
        // TODO default options should be set in DI
        $config  = Config::getInstance()->cache;
        $options = array_merge(array('namespace' => 'tracker'), $config);

        return $options;
    }

    private static function getBackendOptions($backend, $options)
    {
        $key = ucfirst($backend) . 'Cache';
        $backendOptions = Config::getInstance()->$key;

        if (!empty($backendOptions)) {
            $options = array_merge($backendOptions, $options); // we need to forward namespace...
        }

        return $options;
    }

    /**
     * @param $backend
     * @param array $options
     * @return Backend
     */
    private static function getCachedBackend($backend, $options)
    {
        if (!array_key_exists($backend, self::$backends)) {
            self::$backends[$backend] = self::buildSpecificBackend($backend, $options);
        }

        return self::$backends[$backend];
    }

    /**
     * @param $backend
     * @param array $options
     * @return Backend
     */
    private static function buildSpecificBackend($backend, $options = array())
    {
        $options = self::getBackendOptions($backend, $options);

        switch ($backend) {
            case 'array':
                return new ArrayCache();

            case 'file':
                return self::getFileCache($options);

            case 'chained':
                return self::getChainedCache($options);

            case 'null':
                return new Cache\Backend\BlackHole();

            default:
                $type    = $backend;
                $backend = null;

                /**
                 * TODO document this event
                 * @ignore this API is not stable yet
                 */
                Piwik::postEvent('Cache.newBackend', array($type, $options, &$backend));

                if (is_object($backend) && $backend instanceof Backend) {
                    return $backend;
                }

                throw new \InvalidArgumentException("Cache backend $backend not valid");
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
            $backends[] = self::getCachedBackend($backendToBuild, $options);
        }

        return new Cache\Backend\Chained($backends);
    }

}