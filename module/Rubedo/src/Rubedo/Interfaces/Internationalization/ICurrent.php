<?php

/**
 * Rubedo -- ECM solution Copyright (c) 2014, WebTales
 * (http://www.webtales.fr/). All rights reserved. licensing@webtales.fr
 * Open Source License
 * ------------------------------------------------------------------------------------------
 * Rubedo is licensed under the terms of the Open Source GPL 3.0 license.
 *
 * @category Rubedo
 * @package Rubedo
 * @copyright Copyright (c) 2012-2014 WebTales (http://www.webtales.fr)
 * @license http://www.gnu.org/licenses/gpl.html Open Source GPL 3.0 license
 */
namespace Rubedo\Interfaces\Internationalization;

/**
 * Determine current localization
 *
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
interface ICurrent
{

    /**
     * Find the current locale for a site
     *
     * @param $siteId
     * @param $forceLocal
     * @param array $browserArray
     * @return string
     */
    public function resolveLocalization($siteId = null, $forceLocal = null, $browserArray = array());

    /**
     * Get the current localization
     *
     * @return string
     */
    public function getCurrentLocalization();
}