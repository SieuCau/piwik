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
        if (empty($options['host']) || empty($options['port'])) {
            throw new \Exception('RedisCache is not configured. Please provide at least a host and a port');
        }

        $redisCache = new Redis($options);

        return $redisCache;
    }

}