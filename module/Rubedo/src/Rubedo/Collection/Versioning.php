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

use Rubedo\Interfaces\Collection\IVersioning;
use WebTales\MongoFilters\Filter;

/**
 * Service to handle Versioning
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
class Versioning extends AbstractCollection implements IVersioning
{

    protected $_indexes = array(
        array(
            'keys' => array(
                'contentId' => 1,
                'contentVersion' => -1
            )
        )
    );

    public function __construct()
    {
        $this->_collectionName = 'Versioning';
        parent::__construct();
    }

    public function addVersion($obj)
    {
        $currentUserService = \Rubedo\Services\Manager::getService('CurrentUser');
        $currentTimeService = \Rubedo\Services\Manager::getService('CurrentTime');

        $createUser = null;
        $createTime = null;
        $version = null;

        $contentId = (string)$obj['_id'];

        $sort = array(
            'publishVersion' => 'desc'
        );

        $filter = Filter::factory('Value');
        $filter->setName('contentId')->setValue($contentId);

        $this->_dataService->addSort($sort);

        $contentVersions = $this->_dataService->read($filter);
        $contentVersions = $contentVersions['data'];

        if (isset($obj['createUser'])) {
            $createUser = $obj['createUser'];
        }
        if (isset($obj['createTime'])) {
            $createTime = $obj['createTime'];
        }
        if (isset($obj['version'])) {
            $version = $obj['version'];
        }

        $version = array(
            'contentId' => $contentId,
            'publishUser' => $currentUserService->getCurrentUserSummary(),
            'publishTime' => $currentTimeService->getCurrentTime(),
            'contentCreateUser' => $createUser,
            'contentCreateTime' => $createTime,
            'contentVersion' => $version
        );

        if (count($contentVersions) > 0) {
            $version['publishVersion'] = $contentVersions[0]['publishVersion'] + 1;

            $version = array_merge($version, $obj['live']);
            if (isset($obj["isProduct"])&&$obj["isProduct"]&&isset($obj["productProperties"])&&is_array($obj["productProperties"])){
                $version["isProduct"]=$obj["isProduct"];
                $version["productProperties"]=$obj["productProperties"];
            }
        } else {
            $version['publishVersion'] = 0;
        }

        // delete the first version in the collection
        if ($version['publishVersion'] === 1&&count($contentVersions) > 0) {
            $this->_dataService->destroy($contentVersions[0]);
        }

        $returnArray = $this->_dataService->create($version);

        return $returnArray;
    }
}
