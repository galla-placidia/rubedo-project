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
namespace Rubedo\Update;

Use Rubedo\Collection\AbstractCollection;
use Rubedo\Services\Manager;
use WebTales\MongoFilters\Filter;

/**
 * Methods for update tool
 *
 * @author jbourdin
 *
 */
class Update010100 extends Update
{

    protected static $toVersion = '1.2.0';

    /**
     * do the upgrade
     *
     * @return boolean
     */
    public static function upgrade()
    {
        static::updateAllItems('Pages');
        static::updateAllItems('Masks');
        static::cleanBlocks();
        return true;
    }

    public static function cleanBlocks()
    {
        $service = Manager::getService('Blocks');
        $updateCond = Filter::factory('OperatorToValue')->setName('blockData.bType')
            ->setOperator('$exists')
            ->setValue(true);
        $data = array(
            '$unset' => array(
                'blockData.champsConfig' => 1
            )
        );
        $options = array(
            'multiple' => true
        );
        $service->customUpdate($data, $updateCond, $options);
        return true;
    }

    /**
     * force an update action on each item ofa collection
     *
     * @param string $collection
     * @return boolean
     */
    public static function updateAllItems($collection)
    {
        $wasFiltered = AbstractCollection::disableUserFilter();
        $service = Manager::getService($collection);
        $list = $service->getList();
        if ($list['count'] > 0) {
            foreach ($list['data'] as $item) {
                $service->update($item);
            }
        }

        AbstractCollection::disableUserFilter($wasFiltered);
        return true;
    }
}