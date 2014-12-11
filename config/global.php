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

    'cache.backend' => DI\factory(function (ContainerInterface $c) {
        if (\Piwik\Development::isEnabled()) {
            $backend = 'null'; // TODO also use in tests? or only 'array'?
        } else {
            $backend = $c->get('old_config.Cache.backend');
        }

        return $backend;
    }),

    'cache.namespace' => DI\factory(function (ContainerInterface $c) {
        if (\Piwik\SettingsServer::isTrackerApiRequest()) {
            $namespace = 'tracker';
        } elseif (\Piwik\Common::isPhpCliMode()) {
            $namespace = 'cli';
        } else {
            $namespace = 'web';
        }

        return $namespace;
    }),

    'Piwik\Cache\Backend' => DI\factory(function (ContainerInterface $c) {

        $backend   = $c->get('cache.backend');
        $namespace = $c->get('cache.namespace');

        $backend = \Piwik\Cache\Factory::getCachedBackend($backend, $namespace);

        return $backend;
    }),

    'Piwik\Cache' => DI\factory(function (ContainerInterface $c) {

        $backend = $c->get('Piwik\Cache\Backend');
        $cache   = new \Piwik\Cache($backend);

        return $cache;
    }),

    'Piwik\Cache\Multi' => DI\factory(function (ContainerInterface $c) {

        if (!Multi::isPopulated()) {
            $backend   = $c->get('Piwik\Cache\Backend');
            $namespace = $c->get('cache.namespace');

            if (SettingsServer::isTrackerApiRequest()) {
                $eventToPersist = 'Tracker.end';
            } else {
                $eventToPersist = 'Request.dispatch.end';
            }

            Multi::populateCache($backend, $namespace, $eventToPersist);
        }

        $cache = new Multi();

        return $cache;
    }),

);
