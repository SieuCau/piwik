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

    public static function buildDefaultCache($options = array())
    {
        $config  = Config::getInstance()->cache;
        $options = array_merge($config, $options);

        return self::buildCache($config['backend'], $options);
    }

    public static function flushAll()
    {
        foreach (self::$backends as $backend) {
            $backend->doFlush();
        }
    }

    /**
     * @param  $backend
     * @param  array $options
     * @return Cache
     */
    public static function buildCache($backend, $options = array())
    {
        $backend = self::buildCachedBackend($backend, $options);
        $cache   = new Cache($backend);

        return $cache;
    }

    /**
     * @param $backend
     * @param array $options
     * @return Backend
     */
    private static function buildCachedBackend($backend, $options)
    {
        $key = $backend . md5(implode('', $options));

        if (empty(self::$backends[$key])) {
            self::$backends[$key] = self::buildBackend($backend, $options);
        }

        return self::$backends[$key];
    }

    /**
     * @param $backend
     * @param array $options
     * @return Backend
     */
    private static function buildBackend($backend, $options = array())
    {
        switch ($backend) {
            case 'array':
                return new ArrayCache();

            case 'file':
                return self::getFileCache($options);

            case 'chained':
                return self::getChainedCache($options);

            case 'persistent':
                return new Cache\Backend\FileStatic();

            default:
                $type    = $backend;
                $backend = null;

                Piwik::postEvent('Cache.newBackend', array($type, $options, &$backend));

                if (is_object($backend) && $backend instanceof Backend) {
                    return $backend;
                }

                throw new \InvalidArgumentException("Cache backend $backend not valid");
        }
    }

    private static function getFileCache($options)
    {
        $tmp  = StaticContainer::getContainer()->get('path.tmp');
        $path = $tmp . '/cache/';

        if (!empty($options['directory'])) {
            $path .= $options['directory'] . '/';
        }

        return new File($path);
    }

    private static function getChainedCache($options)
    {
        $cache = new Cache\Backend\Chained();

        foreach ($options['backends'] as $backendToBuild) {
            $backend = self::buildCachedBackend($backendToBuild, $options);
            $cache->addBackend($backend);
        }

        return $cache;
    }

}