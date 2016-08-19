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
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

/**
 * BO Logout controller
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
class LogoutController extends AbstractActionController
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
     * Redirect the user to the login page if he's not connected
     */
    public function indexAction()
    {
        if ($this->_auth->hasIdentity()) {
            $this->_auth->clearIdentity();

            $response['success'] = true;
        }
        Manager::getService('Session')->getSessionObject()->getManager()->regenerateId(true);


        if ($this->getRequest()->isXmlHttpRequest()) {
            return new JsonModel($response);
        } else {
            $redirectParams = array(
                'action' => 'index',
                'controller' => 'login'
            );
            return $this->redirect()->toRoute(null, $redirectParams);
        }
    }

    public function confirmLogoutAction()
    {
    }
}

