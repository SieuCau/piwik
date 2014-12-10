<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CacheProvider\Cache\Backend;

use Doctrine\Common\Cache\RedisCache;
use Piwik\Cache\Backend;
use Piwik\Common;

/**
 * This class is used to cache data on the filesystem.
 */
class Redis extends RedisCache implements Backend
{
    public function __construct($options)
    {
        $redis = new \Redis();

        $timeout = (float) Common::forceDotAsSeparatorForDecimalPoint($options['timeout']);

        $redis->connect($options['host'], $options['port'], $timeout);

        if (!empty($options['password'])) {
            $redis->auth($options['password']);
        }

        if (array_key_exists('database', $options)) {
            $redis->select((int) $options['database']);
        }

        $this->setRedis($redis);
    }

    public function doFetch($id)
    {
        return parent::doFetch($id);
    }

    public function doContains($id)
    {
        return parent::doContains($id);
    }

    public function doSave($id, $data, $lifeTime = 0)
    {
        return parent::doSave($id, $data, $lifeTime);
    }

    public function doDelete($id)
    {
        return parent::doDelete($id);
    }

    public function doFlush()
    {
        return parent::doFlush();
    }

}
