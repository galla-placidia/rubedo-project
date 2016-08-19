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
 * Controller providing CRUD API for the user types JSON
 *
 * Receveive Ajax Calls for read & write from the UI to the Mongo DB
 *
 *
 * @author aDobre
 * @category Rubedo
 * @package Rubedo
 *
 */
class UserTypesController extends DataAccessController
{
    protected $_readOnlyAction = array(
        'index',
        'find-one'
    );

    public function __construct()
    {
        parent::__construct();

        // init the data access service
        $this->_dataService = Manager::getService('UserTypes');
    }

    public function isUsedAction()
    {
        $id = $this->params()->fromQuery('id');
        $wasFiltered = AbstractCollection::disableUserFilter();
        $result = Manager::getService('Users')->isTypeUsed($id);
        AbstractCollection::disableUserFilter($wasFiltered);
        return $this->_returnJson($result);
    }

}