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
$boViewsPath = realpath(__DIR__ . '/../src/Rubedo/Backoffice/views/scripts');
$installViewPath = realpath(__DIR__ . '/../src/Rubedo/Install/views/scripts');
$blockViewPath = realpath(__DIR__ . '/../src/Rubedo/Blocks/views/scripts');
$foViewsPath = realpath(__DIR__ . '/../src/Rubedo/Frontoffice/views/scripts');

/**
 * Standard ZF2 views for Rubedo (when not using twig)
 */
return array(
    'layout/layout' => __DIR__ . '/../view/layout/layout.phtml',
    'layout/install' => $installViewPath . '/install-layout.phtml',
    'error/404' => __DIR__ . '/../view/error/404.phtml',
    'error/index' => __DIR__ . '/../view/error/index.phtml',
    'rubedo/controller/index/index' => $boViewsPath . '/index/index.phtml',
    'rubedo/controller/content-contributor/index' => $boViewsPath . '/content-contributor/index.phtml',
    'rubedo/controller/ext-finder/index' => $boViewsPath . '/ext-finder/index.phtml',
    'rubedo/controller/link-finder/index' => $boViewsPath . '/link-finder/index.phtml',
    'rubedo/controller/login/index' => $boViewsPath . '/login/index.phtml',
    'rubedo/install/controller/index/form' => $installViewPath . '/index/form.phtml',
    'rubedo/install/controller/index/element' => $installViewPath . '/index/element.phtml',
    'rubedo/install/controller/index/fieldset' => $installViewPath . '/index/fieldset.phtml',
    'rubedo/install/controller/index/index' => $installViewPath . '/index/index.phtml',
    'rubedo/install/controller/index/start-wizard' => $installViewPath . '/index/start-wizard.phtml',
    'rubedo/install/controller/index/set-db' => $installViewPath . '/index/set-db.phtml',
    'rubedo/install/controller/index/set-elastic-search' => $installViewPath . '/index/set-elastic-search.phtml',
    'rubedo/install/controller/index/define-languages' => $installViewPath . '/index/define-languages.phtml',
    'rubedo/install/controller/index/set-admin' => $installViewPath . '/index/set-admin.phtml',
    'rubedo/install/controller/index/set-db-contents' => $installViewPath . '/index/set-db-contents.phtml',
    'rubedo/install/controller/index/set-local-domains' => $installViewPath . '/index/set-local-domains.phtml',
    'rubedo/install/controller/index/set-mailer' => $installViewPath . '/index/set-mailer.phtml',
    'rubedo/install/controller/index/set-php-settings' => $installViewPath . '/index/set-php-settings.phtml',
    'rubedo/contact/render-form' => $blockViewPath . '/contact/form.phtml',
    'rubedo/index/index' => $foViewsPath . '/index/index.phtml'
);
