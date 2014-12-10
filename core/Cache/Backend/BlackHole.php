<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Cache\Backend;

/**
 * Can be used in tests and development. Does not save anything, ever.
 */
class BlackHole implements \Piwik\Cache\Backend
{

    public function doFetch($id)
    {
        return false;
    }

    public function doContains($id)
    {
        return false;
    }

    public function doSave($id, $data, $lifeTime = 0)
    {
        return true;
    }

    public function doDelete($id)
    {
        return true;
    }

    public function doFlush()
    {
        return true;
    }

}
