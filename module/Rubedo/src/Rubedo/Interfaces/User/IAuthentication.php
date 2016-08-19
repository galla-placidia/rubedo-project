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
namespace Rubedo\Interfaces\User;

/**
 * Authentication Service
 *
 * Authenticate user and get information about him
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
interface IAuthentication
{

    /**
     * Authenticate the user and set the session
     *
     * @param String $login
     *            the login of the user
     * @param String $password
     *            the password of the user
     *
     * @return bool
     */
    public function coreAuthenticate($login, $password);

    /**
     * Return the identity of the current user in session
     *
     * @return array
     */
    public function getIdentity();

    /**
     * Return true if there is a user connected
     *
     * @return bool
     */
    public function hasIdentity();

    /**
     * Unset the session of the current user
     *
     * @return bool
     */
    public function clearIdentity();

    /**
     * Ask a reauthentification without changing the session
     *
     * @param String $login
     *            the login of the user
     * @param String $password
     *            the password of the user
     *
     * @return bool
     */
    public function forceReAuth($login, $password);

    /**
     * reset the Session expiration date to the full duration
     */
    public function resetExpirationTime();

    /**
     * return the remaining duration of the session in seconds
     *
     * @return integer
     */
    public function getExpirationTime();

    /**
     * the default session duration
     *
     * @return $_authLifetime
     */
    public static function getAuthLifetime();

    /**
     * Set the default session duration
     *
     * @param integer $_authLifetime
     */
    public static function setAuthLifetime($_authLifetime);
}
