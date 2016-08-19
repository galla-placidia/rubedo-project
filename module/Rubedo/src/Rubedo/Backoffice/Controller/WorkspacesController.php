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
use Zend\Json\Json;

/**
 * Controller providing CRUD API for the Workspaces JSON
 *
 * Receveive Ajax Calls for read & write from the UI to the Mongo DB
 *
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 *
 */
class WorkspacesController extends DataAccessController
{

    public function __construct()
    {
        parent::__construct();

        // init the data access service
        $this->_dataService = Manager::getService('Workspaces');
    }

    /*
     * (non-PHPdoc) @see DataAccessController::indexAction()
     */
    public function indexAction()
    {
        $filterJson = $this->params()->fromQuery('filter');
        if (isset($filterJson)) {
            $filters = Json::decode($filterJson, Json::TYPE_ARRAY);
        } else {
            $filters = null;
        }
        $sortJson = $this->params()->fromQuery('sort');
        if (isset($sortJson)) {
            $sort = Json::decode($sortJson, Json::TYPE_ARRAY);
        } else {
            $sort = null;
        }
        $startJson = $this->params()->fromQuery('start');
        if (isset($startJson)) {
            $start = Json::decode($startJson, Json::TYPE_ARRAY);
        } else {
            $start = null;
        }
        $limitJson = $this->params()->fromQuery('limit');
        if (isset($limitJson)) {
            $limit = Json::decode($limitJson, Json::TYPE_ARRAY);
        } else {
            $limit = null;
        }

        $mongoFilters = $this->_buildFilter($filters);

        $notAll = $this->params()->fromQuery('notAll', false);
        if ($notAll) {
            $mongoFilters->addFilter(new \Rubedo\Mongo\NotAllWorkspacesFilter());
        }

        $dataValues = $this->_dataService->getList($mongoFilters, $sort, $start, $limit);

        $response = array();
        $response['total'] = $dataValues['count'];
        $response['data'] = $dataValues['data'];
        $response['success'] = TRUE;
        $response['message'] = 'OK';

        return $this->_returnJson($response);
    }
}