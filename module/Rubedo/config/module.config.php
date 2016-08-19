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

/**
 * Construct the whole configuration from split configurations files.
 */
$serviceMapArray = include(__DIR__ . '/services.config.php');
$serviceAliasMapArray = include(__DIR__ . '/services.alias.config.php');
$sharedService = include(__DIR__ . '/shared.services.config.php');
$controllerArray = include(__DIR__ . '/controllers.config.php');
$viewArray = include(__DIR__ . '/views.config.php');
$localizationConfig = include(__DIR__ . '/localization.config.php');
$router = include(__DIR__ . '/router.config.php');
$consoleRouter = include(__DIR__ . '/router.console.config.php');
$blocksDefinition = include(__DIR__ . '/blocks.definition.config.php');
$templateConfig = include(__DIR__ . '/templates.config.php');
$appExtension = include(__DIR__ . '/app.extensions.config.php');
$loggerConfig = include(__DIR__ . '/logger.config.php');
$paymentMeans = include(__DIR__ . '/payment.means.config.php');

foreach ($serviceMapArray as $key => $value) {
    if (in_array($key, $sharedService)) {
        $serviceSharedMapArray[$key] = true;
    } else {
        $serviceSharedMapArray[$key] = false;
    }
}

$config = array(
    'console' => array(
        'router' => $consoleRouter
    ),
    'router' => $router,
    'controllers' => array(
        'invokables' => $controllerArray
    ),
    'view_manager' => array(
        'display_not_found_reason' => true,
        'display_exceptions' => true,
        'doctype' => 'HTML5',
        'not_found_template' => 'error/404',
        'exception_template' => 'error/index',
        'template_map' => $viewArray,
        'template_path_stack' => array(
            __DIR__ . '/../view'
        ),
        'strategies' => array(
            'ViewJsonStrategy',
            'TwigViewStrategy',
            'RawViewStrategy'
        )
    ),
    'service_manager' => array(
        'invokables' => $serviceMapArray,
        'shared' => $serviceSharedMapArray,
        'factories' => array(
            'TwigViewStrategy' => 'Rubedo\\Templates\\Twig\\TwigStrategyFactory',
            'RawViewStrategy' => 'Rubedo\\Templates\\Raw\\RawStrategyFactory'
        ),
        'aliases' => $serviceAliasMapArray,
    ),
    'backoffice' => array(
        'extjs' => array(
            'debug' => '0',
            'network' => 'local',
            'version' => '4.1.1'
        )
    ),
    'localisationfiles' => $localizationConfig,
    'site' => array(),
    'applicationSettings' => array(),
    'rolesDirectories' => array(
        __DIR__ . '/roles'
    )
);

$sessionLifeTime = 3600 * 48;

$config['session'] = array(
    'remember_me_seconds' => $sessionLifeTime,
    'use_cookies' => true,
    'gc_maxlifetime' => $sessionLifeTime,
    'name' => 'rubedo',
    'cookie_httponly' => true,
    'cookiePath' => '/'
);

$config['datastream'] = array();

$config['datastream']['mongo'] = array(
    'server' => 'localhost',
    'port' => '27017',
    'db' => 'rubedo',
    'login' => '',
    'password' => ''
);

$config['elastic'] = array(
    "host" => "localhost",
    "port" => "9200",
    "contentIndex" => "contents",
    "damIndex" => "dam",
    "userIndex" => "user",
    "configFilePath" => __DIR__ . '/elastica.json'
);

$config['blocksDefinition'] = $blocksDefinition;

$config['templates'] = $templateConfig;

$config['appExtension'] = $appExtension;

$config['logger'] = $loggerConfig;

$config['paymentMeans'] = $paymentMeans;

return $config;
