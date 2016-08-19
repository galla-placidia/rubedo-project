<?php
/**
 * Rubedo -- ECM solution
 * Copyright (c) 2014, WebTales (http://www.webtales.fr/).
 * All rights reserved.
 * licensing@webtales.fr
 *
 * Open Source License
 * ------------------------------------------------------------------------------------------
 * Rubedo is licensed under the terms of the Open Source GPL 3.0 license.
 *
 * @category   Rubedo
 * @package    Rubedo
 * @copyright  Copyright (c) 2012-2014 WebTales (http://www.webtales.fr)
 * @license    http://www.gnu.org/licenses/gpl.html Open Source GPL 3.0 license
 */
namespace Rubedo\Services;

use Zend\Cache\StorageFactory;
use Zend\EventManager\EventInterface;
use Rubedo\Exceptions\Server;
use Monolog\Logger;
use Rubedo\Cache\MongoCache;

/**
 * Cache manager
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
class Cache
{

    /**
     * array of current service parameters
     *
     * @var array
     */
    protected static $_cacheOptions;

    /**
     * Setter of services parameters, to init them from bootstrap
     *
     * @param array $options
     */
    public static function setOptions($options)
    {
        self::$_cacheOptions = $options;
    }

    /**
     * getter of services parameters, to init them from bootstrap
     *
     * @return array array of all the services
     */
    public static function getOptions()
    {
        return self::$_cacheOptions;
    }

    /**
     * Public static method to get an instance of the cache
     *
     * @param string $cacheName
     *            name of the cache called
     * @return StorageFactory instance of the cache
     */
    public static function getCache($cacheName = null)
    {
        unset($cacheName); //Unused for the moment

        $cache = StorageFactory::factory(array(
            'adapter' => 'Rubedo\\Cache\\MongoCache'
        ));

        return $cache;
    }

    /**
     * Listener for event on cachable results
     *
     * @param EventInterface $e
     * @return mixed NULL
     */
    public static function getFromCache(EventInterface $e)
    {
        $params = $e->getParams();
        if (!isset($params['key'])) {
            throw new Server('This function should receive a key parameter');
        }
        $key = $params['key'];

        $cache = Cache::getCache();
        $loaded = false;
        $result = $cache->getItem($key, $loaded);
        if ($loaded) {
            $e->stopPropagation(true);
            return $result;
        } else {
            return null;
        }
    }

    /**
     * listener for event to cache current result
     *
     * @param EventInterface $e
     */
    public static function setToCache(EventInterface $e)
    {
        $params = $e->getParams();
        if (!isset($params['key'])) {
            throw new Server('This function should receive a key parameter');
        }
        if (!isset($params['result'])) {
            throw new Server('This function should receive a result parameter');
        }
        $key = $params['key'];
        $result = $params['result'];
        $cache = Cache::getCache();
        $cache->setItem($key, $result);
    }

    /**
     * listener to cache event
     *
     * Will log the hit and miss
     *
     * @param EventInterface $e
     */
    public static function logCacheHit(EventInterface $e)
    {
        $params = $e->getParams();
        if (!isset($params['key'])) {
            throw new Server('This function should receive a key parameter');
        }
        $e->getName();
        switch ($e->getName()) {
            case (MongoCache::CACHE_HIT):
                $message = 'Cache hit on the key: ' . $params['key'];
                $level = Logger::INFO;
                break;
            case (MongoCache::CACHE_MISS):
                $message = 'Cache miss for the key: ' . $params['key'];
                $level = Logger::NOTICE;
                break;
        }
        Manager::getService('Logger')->addRecord($level, $message, array(
            'key' => $params['key']
        ));
    }
}
