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
namespace Rubedo\Update;

/**
 * Methods
 * for
 * update
 * tool
 *
 * @author jbourdin
 *
 */
class Update010402 extends Update
{

    protected static $toVersion = '1.4.3';

    /**
     * do
     * the
     * upgrade
     *
     * @return boolean
     */
    public static function upgrade()
    {
        return true;
    }


}