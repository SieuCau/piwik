<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CacheProvider\Cache;

use Piwik\Cache;
use Piwik\Cache\Backend;
use Piwik\Common;
use Piwik\Config;
use Piwik\Plugins\CacheProvider\Cache\Backend\ArrayCache;
use Piwik\Plugins\CacheProvider\Cache\Backend\Memcache;
use Piwik\Plugins\CacheProvider\Cache\Backend\Redis;

class Factory
{
    /**
     * @param  $backend
     * @param  array $options
     * @return Backend
     */
    public static function buildBackend($backend, $options = array())
    {
        switch ($backend) {
            case 'redis':
                return self::getRedisCache($options);
        }
    }

    private static function getRedisCache($options)
    {
        $redis = new \Redis();

        $config  = Config::getInstance()->RedisCache;
        $options = array_merge($config, $options);

        if (empty($options['host']) || empty($options['port'])) {
            throw new \Exception('RedisCache is not configured correctly. Please provide a host and a port');
        }

        $timeout = (float) Common::forceDotAsSeparatorForDecimalPoint($options['timeout']);

        $redis->connect($options['host'], $options['port'], $timeout);

        if (!empty($options['password'])) {
            $redis->auth($options['password']);
        }

        if (array_key_exists('database', $options)) {
            $redis->select((int) $options['database']);
        }

        $redisCache = new Redis();
        $redisCache->setRedis($redis);

        return $redisCache;
    }

}