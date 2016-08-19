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
 * BO Login Controller
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
class LoginController extends AbstractExtLoaderController
{

    /**
     * Variable for Authentication service
     *
     * @param
     *            Rubedo\Interfaces\User\IAuthentication
     */
    protected $_auth;

    /**
     * Init the authentication service
     */
    public function __construct()
    {
        $this->_auth = Manager::getService('AuthenticationService');
    }

    /**
     * Redirect the user to the backoffice if he's connected
     */
    public function indexAction()
    {
        if ($this->_auth->getIdentity()) {
            $backofficeUrl = $this->request->getBasePath() . '/backoffice/';
            if ($this->params()->fromQuery('content')) {
                $backofficeUrl .= '?content=' . $this->params()->fromQuery('content');
            }

            return $this->redirect()->toUrl($backofficeUrl);
        }
        //Manager::getService('Session')->getSessionObject()->getManager()->regenerateId(true);
        return $this->loadExtApps();
    }
}

