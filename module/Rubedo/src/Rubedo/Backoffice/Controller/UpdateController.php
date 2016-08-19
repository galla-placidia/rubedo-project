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

use Zend\Mvc\Controller\AbstractActionController;
use Rubedo\Services\Manager;
use Rubedo\Update\Update;
use Zend\View\Model\JsonModel;

/**
 * Installer Controller
 *
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
class UpdateController extends AbstractActionController
{

    public function indexAction()
    {
        $rubedoDbVersionService = Manager::getService('RubedoVersion');

        $result = array(
            'needUpdate' => !$rubedoDbVersionService->isDbUpToDate()
        );
        return new JsonModel($result);
    }

    public function runAction()
    {
        $result = array(
            'success' => true,
            'version' => Update::update()
        );
        return new JsonModel($result);
    }

    public function applyIndexesAction(){
        $serviceArray=Manager::getService("config")["service_manager"]["invokables"];
        $collectionServicesArray = array();
        foreach ($serviceArray as $name => $class) {
            if (class_exists($class)&&in_array('Rubedo\\Collection\\AbstractCollection', class_parents($class))) {
                $collectionServicesArray[] = $name;
            }
        }
        $result = true;
        foreach ($collectionServicesArray as $service) {
            if (!Manager::getService($service)->checkIndexes()) {
                Manager::getService($service)->dropIndexes();
                $result = $result && Manager::getService($service)->ensureIndexes();
            }
        }
        return new JsonModel(["success"=>$result]);
    }
}

