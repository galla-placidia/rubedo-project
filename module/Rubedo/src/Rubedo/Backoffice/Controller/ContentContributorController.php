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

/**
 * Controller for handling FO contributions
 *
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
class ContentContributorController extends AbstractExtLoaderController
{

    public function indexAction()
    {
        $this->_auth = Manager::getService('AuthenticationService');

        if (!$this->_auth->getIdentity()) {
            return $this->redirect()->toUrl("/backoffice/login");
        }

        return $this->loadExtApps();
    }
}

