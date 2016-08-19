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

use Rubedo\Interfaces\Collection\ICache;
use Rubedo\Services\Manager;
use WebTales\MongoFilters\Filter;

/**
 * Service to handle cached contents
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
class Cache extends AbstractCollection implements ICache
{

    protected $_indexes = array(
        array(
            'keys' => array(
                'cacheId' => 1
            ),
            'options' => array(
                'unique' => true
            )
        )
    );

    protected $_model = array(
        'data' => array(
            'domain' => 'string',
            'required' => true
        ),
        'cacheId' => array(
            'domain' => 'string',
            'required' => true
        ),
        'expire' => array(
            'domain' => 'tstamp',
            'required' => false
        ),
        'tags' => array(
            'domain' => 'list',
            'required' => false,
            'items' => array(
                'domain' => 'string',
                'required' => false
            )
        )
    );

    public function __construct()
    {
        $this->_collectionName = 'Cache';
        parent::__construct();

        // randomly call cleanning on expired cache entries
        $factor = 100;
        if (rand(1, $factor) % $factor === 0) {
            $this->deleteExpired();
        }
    }

    public function findByCacheId($cacheId, $time = null)
    {
        if (!$time) {
            $time = Manager::getService('CurrentTime')->getCurrentTime();
        }
        $Filters = Filter::factory('And');

        $Filter = Filter::factory('Value')->setName('cacheId')->setValue($cacheId);
        $Filters->addFilter($Filter);

        $Filter = Filter::factory('EmptyOrOperator');
        $Filter->setName('expire')
            ->setOperator('$gt')
            ->setValue($time);
        $Filters->addFilter($Filter);

        return $this->_dataService->findOne($Filters);
    }

    /**
     * Update object or insert if not present base on the CacheId field
     *
     * @param array $obj
     *            inserted data
     * @param string $cacheId
     *            string parameter of the cache entry
     * @return bool
     */
    public function upsertByCacheId($obj, $cacheId)
    {
        $this->_filterInputData($obj);
        $options = array();
        $options['upsert'] = true;

        $updateCond = Filter::factory('Value');
        $updateCond->setName('cacheId')->setValue($cacheId);

        $result = $this->_dataService->customUpdate($obj, $updateCond, $options);
        if ($result['success']) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Remove expired cache items
     *
     * Use Fire And Forget query : do not wait for result
     */
    public function deleteExpired()
    {
        $options = array(
            'multiple' => true,
            'w' => false
        );

        $updateCond = Filter::factory('OperatorToValue');
        $updateCond->setName('expire')
            ->setOperator('$lt')
            ->setValue(Manager::getService('CurrentTime')->getCurrentTime());

        $this->_dataService->customDelete($updateCond, $options);
    }

    public function deleteByCacheId($id)
    {
        $updateCond = Filter::factory('Value');
        $updateCond->setName('cacheId')->setValue($id);
        $options = array();
        $result = $this->_dataService->customDelete($updateCond, $options);
        if ($result['success']) {
            return true;
        } else {
            return false;
        }
    }
}
