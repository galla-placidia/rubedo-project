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
namespace Rubedo\Collection;

use Rubedo\Interfaces\Collection\IUrlCache;
use Rubedo\Services\Manager;
use WebTales\MongoFilters\Filter;
use Zend\EventManager\EventInterface;

/**
 * Service to handle Users
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
class UrlCache extends AbstractCollection implements IUrlCache
{

    protected $_indexes = array(
        array(
            'keys' => array(
                "url" => 1,
                'siteId' => 1
            ),
            'options' => array(
                'unique' => true
            )
        ),
        array(
            'keys' => array(
                'siteId' => 1,
                "pageId" => 1
            ),
            'options' => array(
                'unique' => true
            )
        ),
        array(
            'keys' => array(
                'pageId' => 1
            )
        )
    );

    protected static $pageToUrl = array();

    protected static $urlToPage = array();

    protected $noDbConnection = false;

    /**
     * Set the collection name
     */
    public function __construct()
    {
        $this->_collectionName = 'UrlCache';
        try {
            parent::__construct();
        } catch (\MongoConnectionException $e) {
            $this->noDbConnection = true;
        }

    }

    /**
     * @see \Rubedo\Interfaces\Collection\IUrlCache::verifyIndexes
     */
    public function verifyIndexes()
    {
        $this->_dataService->ensureIndex(array(
            'url' => 1,
            'siteId' => 1
        ), array(
            'unique' => true
        ));
        $this->_dataService->ensureIndex(array(
            'date' => 1
        ), array(
            'expireAfterSeconds' => 600
        ));
    }

    /**
     * @see \Rubedo\Interfaces\Collection\IUrlCache::findByPageId
     */
    public function findByPageId($pageId, $locale)
    {
        if ($pageId instanceof \MongoId) {
            $pageId = (string)$pageId;
        }
        if ($this->noDbConnection) {
            return null;
        }
        if (!isset(static::$pageToUrl[$locale][$pageId])) {
            $filters = Filter::factory();
            $filters->addFilter(
                Filter::factory('Value')
                    ->setName('pageId')
                    ->setValue($pageId)
            );
            $filters->addFilter(
                Filter::factory('Value')
                    ->setName('locale')
                    ->setValue($locale)
            );
            $filters->addFilter(
                Filter::factory('OperatorToValue')
                    ->setName('content-id')
                    ->setOperator('$exists')
                    ->setValue(false)
            );
            static::$pageToUrl[$locale][$pageId] = $this->_dataService->findOne($filters);
        }
        return static::$pageToUrl[$locale][$pageId];
    }

    public function create(array $obj, $options = array('w' => false))
    {
        $obj['date'] = $this->_dataService->getMongoDate();

        parent::create($obj, $options);
    }

    /**
     * @see \Rubedo\Interfaces\Collection\IUrlCache::findByUrl
     */
    public function findByUrl($url, $siteId)
    {
        if ($this->noDbConnection) {
            return null;
        }
        if (!$siteId) {
            return null;
        }
        if (!isset(static::$urlToPage[$siteId]) || !isset(static::$urlToPage[$siteId][$url])) {
            $filters = Filter::factory('And');

            $filter = Filter::factory('Value');
            $filter->setName('url')->setValue($url);
            $filters->addFilter($filter);

            $filter = Filter::factory('Value');
            $filter->setName('siteId')->setValue($siteId);
            $filters->addFilter($filter);

            static::$urlToPage[$siteId][$url] = $this->_dataService->findOne($filters);
        }
        return static::$urlToPage[$siteId][$url];
    }

    /**
     * @see \Rubedo\Interfaces\Collection\IUrlCache::urlToPageReadCacheEvent
     */
    public function urlToPageReadCacheEvent(EventInterface $event)
    {
        $params = $event->getParams();
        $result = $this->findByUrl($params['url'], $params['siteId']);
        if ($result) {
            $message = 'cache hit for current URL ' . Manager::getService('Application')->getRequest()->getUri();
            Manager::getService('Logger')->debug($message);
            $event->stopPropagation();
            unset($result['date']);
            unset($result['siteId']);
            unset($result['url']);
            unset($result['version']);
            unset($result['lastUpdateUser']);
            unset($result['createUser']);
            unset($result['createTime']);
            return $result;
        }
    }

    /**
     * @see \Rubedo\Interfaces\Collection\IUrlCache::pageToUrlReadCacheEvent
     */
    public function pageToUrlReadCacheEvent(EventInterface $event)
    {
        $params = $event->getParams();
        $result = $this->findByPageId($params['pageId'], $params['locale']);
        if ($result) {
            $message = 'cache hit for pageUrl ' . $result['url'];
            Manager::getService('Logger')->debug($message);
            $event->stopPropagation();
            return $result['url'];
        }
    }

    /**
     * @see \Rubedo\Interfaces\Collection\IUrlCache::urlToPageWriteCacheEvent
     */
    public function urlToPageWriteCacheEvent(EventInterface $event)
    {
        if ($this->noDbConnection) {
            return null;
        }
        $data = $event->getParams();
        $this->create($data);
    }
}
