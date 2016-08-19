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
namespace Rubedo\Backoffice;

use Rubedo\Services\Manager;

/**
 * Configuration for ExtJs version
 *
 * @author jbourdin
 *
 */
class ExtConfig
{

    /**
     * ExtJs configuration
     *
     * @var array
     */
    protected static $config;

    /**
     *
     * @return the $config
     */
    public static function getConfig()
    {
        if (!isset(self::$config)) {
            self::lazyloadConfig();
        }
        return ExtConfig::$config;
    }

    /**
     *
     * @param multitype : $config
     */
    public static function setConfig($config)
    {
        ExtConfig::$config = $config;
    }

    /**
     * Read configuration from global application config and load it for the current class
     */
    public static function lazyloadConfig()
    {
        $config = Manager::getService('config');
        $options = $config['backoffice']['extjs'];
        self::setConfig($options);
    }
}
