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
use Piwik\Container\StaticContainer;

class Cache
{

    /**
     * This cache will persist any set data in the configured backend.
     * @return Cache\Lazy
     */
    public static function getLazyCache()
    {
        return StaticContainer::getContainer()->get('Piwik\Cache\Lazy');
    }

    /**
     * This cache will not persist any data it contains. It will be only cached during one request. While the persistent
     * cache cannot cache objects this one can cache any kind of data.
     * @return Cache\Transient
     */
    public static function getTransientCache()
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
     * @return Cache\Eager
     */
    public static function getEagerCache()
    {
        return StaticContainer::getContainer()->get('Piwik\Cache\Eager');
    }

    public static function flushAll()
    {
        self::getLazyCache()->flushAll();
        self::getTransientCache()->flushAll();
        self::getEagerCache()->flushAll();
    }

    /**
     * @param $type
     * @return Cache\Backend
     */
    public static function buildBackend($type)
    {
        $factory = new Cache\Backend\Factory();
        $options = self::getOptions($type);

        $backend = $factory->buildBackend($type, $options);

        return $backend;
    }

    private static function getOptions($type)
    {
        $options = self::getBackendOptions($type);

        switch ($type) {
            case 'file':

                $options = array('directory' => StaticContainer::getContainer()->get('path.cache'));
                break;

            case 'chained':

                foreach ($options['backends'] as $backend) {
                    $options[$backend] = self::getOptions($backend);
                }

                break;

            case 'redis':

                if (!empty($options['timeout'])) {
                    $options['timeout'] = (float)Common::forceDotAsSeparatorForDecimalPoint($options['timeout']);
                }

                break;
        }

        return $options;
    }

    private static function getBackendOptions($backend)
    {
        $key = ucfirst($backend) . 'Cache';
        $options = Config::getInstance()->$key;

        return $options;
    }
}
