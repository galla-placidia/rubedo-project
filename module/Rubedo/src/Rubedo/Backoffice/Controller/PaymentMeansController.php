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

use Zend\Json\Json;
use Zend\Mvc\Controller\AbstractActionController;
use Rubedo\Services\Manager;
use Zend\View\Model\JsonModel;

/**
 * Controller providing access to payment means
 *
 *
 *
 * @author adobre
 * @category Rubedo
 * @package Rubedo
 *
 */
class PaymentMeansController extends AbstractActionController
{

    public function indexAction()
    {
        $config = Manager::getService('config');
        $data = $config['paymentMeans'];
        $refinedData = array();
        foreach ($data as $key => $value) {
            $value['id'] = $key;
            $pmJsonData = file_get_contents($value['definitionFile']);
            $value['configFields'] = Json::decode($pmJsonData, Json::TYPE_ARRAY);
            unset ($value['definitionFile']);
            unset ($value['controller']);
            $refinedData[] = $value;
        }
        return new JsonModel(array(
            "data" => $refinedData,
            "success" => true,
            "total" => count($refinedData)
        ));
    }
}
