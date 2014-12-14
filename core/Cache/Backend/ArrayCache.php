<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 *
 */
namespace Piwik\Cache\Backend;

use Piwik\Cache\Backend;

/**
 * This class is used to cache data on the filesystem.
 */
class ArrayCache extends \Doctrine\Common\Cache\ArrayCache implements Backend
{

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
