<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Plugins\CacheProvider;
use Piwik\Plugins\CacheProvider\Cache\Factory;

class CacheProvider extends \Piwik\Plugin
{

    public function getListHooksRegistered()
    {
        return array(
            'Cache.newBackend' => 'buildBackend',
        );
    }

    public function buildBackend($type, $options, &$backend)
    {
        $cacheBackend = Factory::buildBackend($type, $options);

        if (!empty($cacheBackend)) {
            $backend = $cacheBackend;
        }
    }
}
