<?php

use Interop\Container\ContainerInterface;
use Piwik\Cache\Multi;
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

        return $root . '/cache';
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

    'cache.namespace' => DI\factory(function (ContainerInterface $c) {
        if (\Piwik\SettingsServer::isTrackerApiRequest()) {
            $namespace = 'tracker';
        } else {
            $namespace = 'web';
        }

        return $namespace;
    }),

    'Piwik\Cache' => DI\object()->method('setNamespace', DI\link('cache.namespace')),
    'Piwik\Cache\Multi' => DI\factory(function (ContainerInterface $c) {

        if (!Multi::isPopulated()) {
            $backend = $c->get('Piwik\Cache\Backend');
            $mode    = $c->get('cache.namespace');

            if (SettingsServer::isTrackerApiRequest()) {
                $eventToPersist = 'Tracker.end';
            } else {
                $eventToPersist = 'Request.dispatch.end';
            }

            Multi::populateCache($backend, $mode, $eventToPersist);
        }

        $cache = new Multi();

        return $cache;
    }),
    'Piwik\Cache\Backend\File' => DI\object()->constructor(DI\link('path.cache')),
    'Piwik\Cache\Backend' => DI\factory(function (ContainerInterface $c) {

        $backend   = $c->get('cache.backend');
        $namespace = $c->get('cache.namespace');

        $backend = \Piwik\Cache\Factory::getCachedBackend($backend, $namespace);

        return $backend;
    }),

);
