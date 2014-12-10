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
 * TODO: extend Doctrine ChainCache as soon as available
 */
class Chained implements \Piwik\Cache\Backend
{
    /**
     * @var Backend[]
     */
    private $backends = array();

    public function __construct($backends = array())
    {
        $this->backends = $backends;
    }

    public function doFetch($id)
    {
        foreach ($this->backends as $key => $backend) {
            if ($backend->doContains($id)) {
                $value = $backend->doFetch($id);

                // We populate all the previous cache layers (that are assumed to be faster)
                // EG If chain is ARRAY => REDIS => DB and we find result in DB we will update REDIS and ARRAY
                for ($subKey = $key - 1 ; $subKey >= 0 ; $subKey--) {
                    $this->backends[$subKey]->doSave($id, $value, 300); // TODO we should use the actual TTL here
                }

                return $value;
            }
        }

        return false;
    }

    public function doContains($id)
    {
        foreach ($this->backends as $backend) {
            if ($backend->doContains($id)) {
                return true;
            }
        }

        return false;
    }

    public function doSave($id, $data, $lifeTime = 0)
    {
        $stored = true;

        foreach ($this->backends as $backend) {
            $stored = $backend->doSave($id, $data, $lifeTime) && $stored;
        }

        return $stored;
    }

    public function doDelete($id)
    {
        $deleted = false;

        foreach ($this->backends as $backend) {
            $deleted = $deleted || $backend->doDelete($id);
        }

        return $deleted;
    }

    public function doFlush()
    {
        $flushed = true;

        foreach ($this->backends as $backend) {
            $flushed = $backend->doFlush() && $flushed;
        }

        return $flushed;
    }

}
