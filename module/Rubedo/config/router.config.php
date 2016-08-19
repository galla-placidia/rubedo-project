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
 * Configure all routes for Rubedo
 */
return array(
    'routes' => array(
        // route for different frontoffice controllers
        'frontoffice' => array(
            'type' => 'Literal',
            'options' => array(
                'route' => '/',
                'defaults' => array(
                    '__NAMESPACE__' => 'Rubedo\Frontoffice\Controller',
                    'controller' => 'Index',
                    'action' => 'index'
                )
            ),
            'may_terminate' => true,
            'child_routes' => array(
                'default' => array(
                    'type' => 'Segment',
                    'options' => array(
                        'route' => '[:controller[/:action]]',
                        '__NAMESPACE__' => 'Rubedo\Frontoffice\Controller',
                        'constraints' => array(
                            'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            'action' => '[a-zA-Z][a-zA-Z0-9_-]*'
                        ),
                        'defaults' => array()
                    )
                )
            )
        ),
        // resolve to URL to pageId
        'rewrite' => array(
            'type' => 'Rubedo\Router\FrontofficeRoute',
            'options' => array(
                'route' => '/',
                'defaults' => array(
                    'controller' => 'Rubedo\Frontoffice\Controller\Index',
                    'action' => 'index'
                )
            )
        ),
        // install route : prefix by install
        'install' => array(
            'type' => 'Segment',
            'options' => array(
                'route' => '/install[/[:controller[/:action]]]',
                'defaults' => array(
                    '__NAMESPACE__' => 'Rubedo\Install\Controller',
                    'controller' => 'Index',
                    'action' => 'index',
                    'constraints' => array(
                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*'
                    )
                )
            ),
            'may_terminate' => true
        ),
        //Sitemap generation
        'sitemap' => array(
            'type' => 'Literal',
            'options' => array(
                'route' => '/sitemap.xml',
                'defaults' => array(
                    '__NAMESPACE__' => 'Rubedo\\Frontoffice\\Controller',
                    'controller' => 'Sitemap',
                    'action' => 'index'
                )
            ),
        ),
        // Payment controller
        'payment' => array(
            'type' => 'Segment',
            'options' => array(
                'route' => '/payment/[:controller[/:action]]',
                'defaults' => array(
                    '__NAMESPACE__' => 'Rubedo\Payment\Controller',
                    'constraints' => array(
                        'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                        'action' => '[a-zA-Z][a-zA-Z0-9_-]*'
                    ),
                    'controller' => 'Index',
                    'action' => 'index'
                )
            ),
            'may_terminate' => true
        ),
        // Backoffice route : prefix by backoffice
        'backoffice' => array(
            'type' => 'Literal',
            'options' => array(
                'route' => '/backoffice',
                'defaults' => array(
                    '__NAMESPACE__' => 'Rubedo\Backoffice\Controller',
                    'controller' => 'Index',
                    'action' => 'index'
                )
            ),
            'may_terminate' => true,
            'child_routes' => array(
                'default' => array(
                    'type' => 'Segment',
                    'options' => array(
                        'route' => '/[:controller[/:action]]',
                        '__NAMESPACE__' => 'Rubedo\Backoffice\Controller',
                        'constraints' => array(
                            'controller' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            'action' => '[a-zA-Z][a-zA-Z0-9_-]*'
                        ),
                        'defaults' => array()
                    )
                )
            )
        ),
        // AppExtension route : prefix by backoffice/app
        'AppExtension' => array(
            'type' => 'Segment',
            'options' => array(
                'route' => '/backoffice/app/appextensions/:app-name/:filepath{-}',
                'defaults' => array(
                    '__NAMESPACE__' => 'Rubedo\Backoffice\Controller',
                    'controller' => 'AppExtension',
                    'action' => 'get-file'
                )
            ),
            'may_terminate' => true
        ),
        // themeResource route : prefix by theme
        'avatar' => array(
            'type' => 'Segment',
            'options' => array(
                'route' => '/user-avatar/:userId/:version[/:width/:height/:mode]/:filename',
                'defaults' => array(
                    '__NAMESPACE__' => 'Rubedo\\Frontoffice\\Controller',
                    'controller' => 'Image',
                    'action' => 'get-user-avatar'
                )
            ),
            'may_terminate' => true
        ),
        // themeResource route : prefix by theme
        'imageFromDam' => array(
            'type' => 'Segment',
            'options' => array(
                'route' => '/generate-image/:mediaId/:version/:width/:height/:mode/:filename',
                'defaults' => array(
                    '__NAMESPACE__' => 'Rubedo\\Frontoffice\\Controller',
                    'controller' => 'Image',
                    'action' => 'generate-dam'
                )
            ),
            'may_terminate' => true
        ),
        // themeResource route : prefix by theme
        'mediaFromDam' => array(
            'type' => 'Segment',
            'options' => array(
                'route' => '/access-dam/:mediaId/:version/:download/:filename',
                'defaults' => array(
                    '__NAMESPACE__' => 'Rubedo\\Frontoffice\\Controller',
                    'controller' => 'Dam',
                    'action' => 'rewrite'
                )
            ),
            'may_terminate' => true
        ),
        // themeResource route : prefix by theme
        'themeResource' => array(
            'type' => 'Segment',
            'options' => array(
                'route' => '/theme/:theme/:filepath{*}',
                'defaults' => array(
                    '__NAMESPACE__' => 'Rubedo\\Frontoffice\\Controller',
                    'controller' => 'Theme',
                    'action' => 'index'
                )
            ),
            'may_terminate' => true
        ),
        'extensionPath' => array(
            'type' => 'Segment',
            'options' => array(
                'route' => '/extension-path/:name/:filepath{*}',
                'defaults' => array(
                    '__NAMESPACE__' => 'Rubedo\\Frontoffice\\Controller',
                    'controller' => 'ExtensionPath',
                    'action' => 'index'
                )
            ),
            'may_terminate' => true
        ),
    )
);