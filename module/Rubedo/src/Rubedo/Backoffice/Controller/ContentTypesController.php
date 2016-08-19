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
namespace Rubedo\Backoffice\Controller;

use Rubedo\Services\Manager;
use Rubedo\Collection\AbstractCollection;
use Zend\Json\Json;

/**
 * Controller providing CRUD API for the field types JSON
 *
 * Receveive Ajax Calls for read & write from the UI to the Mongo DB
 *
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 *
 */
class ContentTypesController extends DataAccessController
{

    public function __construct()
    {
        parent::__construct();

        // init the data access service
        $this->_dataService = Manager::getService('ContentTypes');
    }

    public function getReadableContentTypesAction()
    {
        return $this->_returnJson($this->_dataService->getReadableContentTypes());
    }

    public function indexAction()
    {
        // merge filter and tFilter
        $jsonFilter = $this->params()->fromQuery('filter', '[]');
        $jsonTFilter = $this->params()->fromQuery('tFilter', '[]');
        $filterArray = Json::decode($jsonFilter, Json::TYPE_ARRAY);
        $tFilterArray = Json::decode($jsonTFilter, Json::TYPE_ARRAY);

        $filters = array_merge($tFilterArray, $filterArray);
        $mongoFilters = $this->_buildFilter($filters);

        $sort = Json::decode($this->params()->fromQuery('sort', null), Json::TYPE_ARRAY);
        $start = Json::decode($this->params()->fromQuery('start', null), Json::TYPE_ARRAY);
        $limit = Json::decode($this->params()->fromQuery('limit', null), Json::TYPE_ARRAY);

        $dataValues = $this->_dataService->getList($mongoFilters, $sort, $start, $limit, false);
        $response = array();
        $response['total'] = $dataValues['count'];
        $response['data'] = $dataValues['data'];
        $response['success'] = TRUE;
        $response['message'] = 'OK';

        return $this->_returnJson($response);
    }

    public function isUsedAction()
    {
        $id = $this->params()->fromQuery('id');
        $wasFiltered = AbstractCollection::disableUserFilter();
        $result = Manager::getService('Contents')->isTypeUsed($id);
        AbstractCollection::disableUserFilter($wasFiltered);
        return $this->_returnJson($result);
    }

    public function isChangeableAction()
    {
        $data = $this->params()->fromPost();
        $newType = Json::decode($data['fields'], Json::TYPE_ARRAY);
        $id = $data['id'];
        $originalType = $this->_dataService->findById($id);
        $originalType = $originalType['fields'];

        $wasFiltered = AbstractCollection::disableUserFilter();
        $isUsedResult = Manager::getService('Contents')->isTypeUsed($id);
        AbstractCollection::disableUserFilter($wasFiltered);
        if (!$isUsedResult['used']) {
            $resultArray = array(
                "modify" => "ok"
            );
        } else {

            $result = $this->_dataService->isChangeableContentType($originalType, $newType);
            $resultArray = ($result == true) ? array(
                "modify" => "possible"
            ) : array(
                "modify" => "no"
            );
        }
        return $this->_returnJson($resultArray);
    }
}
