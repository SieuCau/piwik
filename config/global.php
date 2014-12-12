<?php

use Interop\Container\ContainerInterface;
use Piwik\Cache\Multi;
use Piwik\Cache\Transient;
use Piwik\SettingsServer;

return array(

    'path.root' => PIWIK_USER_PATH,

    'path.tmp' => DI\factory(function (ContainerInterface $c) {
        $root = $c->get('path.root');

        // TODO remove that special case and instead have plugins override 'path.tmp' to add the instance id
        if ($c->has('old_config.General.instance_id')) {
            $instanceId = $c->get('old_config.General.instance_id');
            $instanceId = $instanceId ? '/' . $instanceId : '';
        } else {
            $instanceId = '';
        }

        return $root . '/tmp' . $instanceId;
    }),

    'path.cache' => DI\factory(function (ContainerInterface $c) {
        $root = $c->get('path.tmp');

        return $root . '/cache/tracker/';
    }),

    'cache.backend' => DI\factory(function (ContainerInterface $c) {
        if (\Piwik\Common::isPhpCliMode()) { // todo replace this with isTest() instead of isCli()
            $backend = 'array';
        } elseif (\Piwik\Development::isEnabled()) {
            $backend = 'null';
        } else {
            $backend = $c->get('old_config.Cache.backend');
        }

        return $backend;
    }),
    'Piwik\Cache' => DI\object(),
    'Piwik\Cache\Transient' => DI\factory(function (ContainerInterface $c) {

        $backend = \Piwik\Cache\Factory::buildBackend('array');

        return new Transient($backend);
    }),
    'Piwik\Cache\Multi' => DI\factory(function (ContainerInterface $c) {

        $multi = new Multi();

        if (!$multi->isPopulated()) {
            $type    = $c->get('cache.backend');
            $backend = \Piwik\Cache\Factory::buildBackend($type);

            if (SettingsServer::isTrackerApiRequest()) {
                $eventToPersist = 'Tracker.end';
                $mode = 'tracker';
            } else {
                $eventToPersist = 'Request.dispatch.end';
                $mode = 'ui';
            }

            $multi->populateCache($backend, $mode);
            \Piwik\Piwik::addAction($eventToPersist, function () use ($multi, $backend, $mode) {
                $multi->persistCache($backend, $mode, 43200);
            });
        }

        return $multi;
    }),
    'Piwik\Cache\Backend\File' => DI\object()->constructor(DI\link('path.cache')),
    'Piwik\Cache\Backend' => DI\factory(function (ContainerInterface $c) {

        $type    = $c->get('cache.backend');
        $backend = \Piwik\Cache\Factory::buildBackend($type);

        return $backend;
    }),

);
