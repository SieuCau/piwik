<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */

namespace Piwik\Tracker;

use Piwik\Cache\Prepopulated;
use Piwik\Settings\Storage;
use Piwik\Tracker;
use Piwik\Cache\Factory as CacheFactory;

/**
 * Loads settings from tracker cache instead of database. If not yet present in tracker cache will cache it.
 */
class SettingsStorage extends Storage
{
    protected function loadSettings()
    {
        $cache = $this->getCache();

        if ($cache->has()) {
            $settings = $cache->get();
        } else {
            $settings = parent::loadSettings();

            $cache->set($settings);
        }

        return $settings;
    }

    public function save()
    {
        parent::save();
        self::clearCache();
    }

    private function getCache()
    {
        return CacheFactory::buildPrepopulatedCache($this->getOptionKey());
    }

    public static function clearCache()
    {
        Cache::deleteTrackerCache();
        Prepopulated::_reset();
    }

}
