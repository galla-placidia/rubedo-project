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
namespace Rubedo\Templates;

use Rubedo\Interfaces\Templates\IFrontOfficeTemplates;
use Rubedo\Services\Manager;
use WebTales\MongoFilters\Filter;
use Zend\Json\Json;

/**
 * Front Office URL service
 *
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
class FrontOfficeTemplates implements IFrontOfficeTemplates
{

    protected static $config;

    /**
     * twig environnelent object
     *
     * @var \Twig_Environment
     */
    protected $_twig;

    /**
     * twig environnelent object
     *
     * @var \Twig_Environment
     */
    protected $twigString;

    /**
     * Twig options array
     *
     * @var array
     */
    protected $options = array();

    /**
     * Directory containing twig templates
     *
     * @var string
     */
    protected static $templateDir = null;

    /**
     * Current theme name
     *
     * @var string
     */
    protected static $_currentTheme = null;

    /**
     * Custom theme id
     *
     * @var string
     */
    protected static $_customThemeId = null;

    /**
     * had main theme been set ?
     *
     * @var boolean
     */
    protected static $_themeHasBeenSet = false;

    protected static $themeVersion = null;

    /**
     *
     * @return the $config
     */
    public function getConfig()
    {
        return FrontOfficeTemplates::$config;
    }

    /**
     *
     * @param field_type $config
     */
    public static function setConfig($config)
    {
        FrontOfficeTemplates::$config = $config;
    }

    /**
     * Constructor
     */
    public function __construct()
    {
        if (!isset(self::$config)) {
            self::lazyloadConfig();
        }
        $this->_init();
    }

    /**
     * initialise Twig Context
     *
     * @param string $lang
     *            current language
     */
    protected function _init()
    {
        $config = $this->getConfig();

        $this->options = array(
            'templateDir' => $config['templateDir'],
            'cache' => $config['cache'],
            'debug' => $config['debug'],
            'auto_reload' => $config['auto_reload'],
            'overrideThemes' => $config['overrideThemes'],
            'rootTemplateDir' => $config['rootTemplateDir']
        );

        $fileSystem = $config['templateDir'] . '/customtheme';
        if ($this->getCurrentTheme() != 'customtheme') {
            $fileSystem = $config['themes'][$this->getCurrentTheme()]['basePath'];
        }
        $loader = new \Twig_Loader_Filesystem($fileSystem);

        //basePath
        $loader->addPath($this->options['templateDir'] . '/root', 'Root');
        $loader->addPath($this->options['templateDir'] . '/root');
        $this->_twig = new \Twig_Environment($loader, $this->options);
        $this
            ->addExtensions($this->_twig)
            ->addFilters($this->_twig)
            ->addFunctions($this->_twig);
        $stringLoader = new \Twig_Loader_String();
        $this->twigString = new \Twig_Environment($stringLoader, $this->options);
        $this
            ->addExtensions($this->twigString)
            ->addFilters($this->twigString)
            ->addFunctions($this->twigString);


        foreach ($config['namespaces'] as $name => $path) {
            $loader->prependPath($path, $name);
        }

        if (isset($this->options['overrideThemes']) && isset($this->options['overrideThemes'][$this->getCurrentTheme()])) {
            $loader->prependPath($this->options['overrideThemes'][$this->getCurrentTheme()]);
        }
    }

    /**
     * render a twig template given an array of data
     *
     * @param string $template
     *            template name
     * @param array $vars
     *            array of data to be rendered
     * @return string HTML produced by twig
     */
    public function render($template, array $vars)
    {
        $templateObj = $this->_twig->loadTemplate($template);
        return $templateObj->render($vars);
    }

    public function renderString($string, array $vars)
    {
        return $this
            ->twigString
            ->loadTemplate($string)
            ->render($vars);
    }

    /**
     * return the template directory
     *
     * @return string
     */
    public function getTemplateDir()
    {
        if (!isset(self::$templateDir)) {
            $config = $this->getConfig();
            $this->options = array(
                'templateDir' => $config['templateDir'],
                'cache' => $config['cache'],
                'debug' => $config['debug'],
                'auto_reload' => $config['auto_reload'],
                'overrideThemes' => $config['overrideThemes'],
                'rootTemplateDir' => $config['rootTemplateDir']
            );

            self::$templateDir = $this->options['templateDir'];
        }
        return self::$templateDir;
    }

    /**
     * Return the actual path of a twig subpart in the current theme
     *
     * Check if it exist in current theme, return default path if not
     *
     * @return string
     */
    public function getFileThemePath($path)
    {
        // no longer use this function for twig : use advanced twig_loader config
        if (pathinfo($path, PATHINFO_EXTENSION) == 'twig') {
            return $path;
        } else {

            if (strpos($path, '@') === 0) {
                $path = str_replace('@', 'ns-', $path);
            }
            return 'theme/' . $this->getCurrentTheme() . '/' . $path;
        }
    }

    public function getFilePath($theme, $path)
    {
        $namespace = null;
        if (strpos($path, 'ns-') === 0) {
            $path = str_replace('ns-', '', $path);
            $segmentArray = explode('/', $path);

            $namespace = array_shift($segmentArray);
            $path = implode('/', $segmentArray);
        }
        // no longer use this function for twig : use advanced twig_loader config
        if (in_array(pathinfo($path, PATHINFO_EXTENSION), array(
            'twig',
            'php'
        ))) {
            return false;
        }
        $config = $this->getConfig();
        if (!isset($config['themes'][$theme])) {
            return false;
        }
        if (isset($config['overrideThemes'][$theme])) {
            $dir = $config['overrideThemes'][$theme];
            if (is_file($dir . '/' . $path)) {
                return $dir . '/' . $path;
            }
        }

        if ($namespace && isset($config['namespaces'][$namespace])) {
            $dir = $config['namespaces'][$namespace];
            if (is_file($dir . '/' . $path)) {
                return $dir . '/' . $path;
            }
        }

        if (isset($config['themes'][$theme]['basePath'])) {
            $dir = $config['themes'][$theme]['basePath'];
            if (is_file($dir . '/' . $path)) {
                return $dir . '/' . $path;
            }
        }

        if (is_file($config['rootTemplateDir'] . '/' . $path)) {
            return $config['rootTemplateDir'] . '/' . $path;
        } else {
            return false;
        }
    }

    public function templateFileExists($path)
    {
        try {
            $this->_twig->resolveTemplate($path);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Get the current theme name
     *
     * @return string
     */
    public function getCurrentTheme()
    {
        if (!isset(self::$_currentTheme)) {
            self::$_currentTheme = 'default';
        }
        return self::$_currentTheme;
    }

    /**
     * Get the custom theme id
     *
     * @return string
     */
    public function getCustomThemeId()
    {
        return self::$_customThemeId;
    }

    /**
     * Get the custom theme version
     *
     * @return string
     */
    public function getCustomThemeVersion()
    {
        return self::$themeVersion;
    }

    /**
     * Set the current theme name
     *
     * @param string $theme
     */
    public function setCurrentTheme($theme)
    {
        // check if it is a custom theme
        if (preg_match('/[\dabcdef]{24}/', $theme) == 1) {
            $themeData = Manager::getService('CustomThemes')->findById($theme);
            if ($themeData) {
                self::$_currentTheme = "customtheme";
                self::$_customThemeId = $theme;
                self::$themeVersion = $themeData['version'];
            } else {
                self::$_currentTheme = 'default';
            }
            self::$_themeHasBeenSet = true;
        } else {
            self::$_currentTheme = $theme;
            self::$_themeHasBeenSet = true;
        }
    }

    public function themeHadBeenSet()
    {
        return self::$_themeHasBeenSet;
    }

    /**
     * Call the Html Cleaner Service
     */
    public static function cleanHtml($html)
    {
        if (is_null($html) || empty($html)) {
            return '';
        }
        return Manager::getService('HtmlCleaner')->clean($html);
    }

    public static function url(array $urlOptions = array(), $reset = false, $encode = true, $route = null)
    {
        return Manager::getService('Url')->url($urlOptions, $route, $reset, $encode);
    }

    public static function displayUrl($contentId, $type = "default", $siteId = null, $defaultUrl = null)
    {
        return Manager::getService('Url')->displayUrl($contentId, $type, $siteId, $defaultUrl);
    }

    public static function imageUrl($mediaId, $width = null, $height = null, $mode = 'crop')
    {
        return Manager::getService('Url')->imageUrl($mediaId, $width, $height, $mode);
    }

    public static function mediaUrl($mediaId, $forceDownload = null)
    {
        return Manager::getService('Url')->mediaUrl($mediaId, $forceDownload);
    }

    public static function mediaThumbnailUrl($mediaId)
    {
        return Manager::getService('Url')->mediaThumbnailUrl($mediaId);
    }

    public static function staticUrl($url)
    {
        return Manager::getService('Url')->staticUrl($url);
    }

    public static function flagUrl($code, $size = 16)
    {
        return Manager::getService('Url')->flagUrl($code, $size);
    }

    public static function userAvatar($userId)
    {
        return Manager::getService('Url')->userAvatar($userId);
    }

    /**
     * Twig function to get user thumbnail
     *
     * @param $userId
     * @param int $width
     * @param int $height
     * @param string $mode crop|morph|boxed
     *
     * @return mixed the url
     */
    public static function userAvatarThumbnail($userId, $width = null, $height = null, $mode = 'morph')
    {
        return Manager::getService('Url')->userAvatar($userId, $width, $height, $mode);
    }

    public static function getPageTitle($contentId)
    {
        $page = Manager::getService('Pages')->findByID($contentId);
        if ($page) {
            return $page['title'];
        } else {
            return null;
        }
    }

    public static function getCountryName($alpha2)
    {
        $country = Manager::getService('Countries')->findOneByAlpha2($alpha2);
        if ($country) {
            return $country['name'];
        } else {
            return $alpha2;
        }
    }

    public static function getLinkedContents($contentId, $typeId, $fieldName, $sort = null)
    {
        return Manager::getService('Contents')->getReflexiveLinkedContents($contentId, $typeId, $fieldName, $sort);
    }

    public static function getTaxonomyTerm($id)
    {
        return Manager::getService('TaxonomyTerms')->findById($id);
    }

    public function getAvailableThemes()
    {
        $themeInfosArray = array();

        // get declared themes
        $config = $this->getConfig();
        foreach ($config['themes'] as $key => $value) {
            $themeInfosArray[] = array(
                'text' => $key,
                'label' => $value['label']
            );
        }

        // get database custom themes
        $contextExist = Filter::factory('OperatorToValue')
            ->setName('context')
            ->setOperator('$exists')
            ->setValue(true);
        $foContext = Filter::factory('Value')
            ->setName('context')
            ->setValue('front');
        $foContextFilters = Filter::factory()
            ->addFilter($contextExist)
            ->addFilter($foContext);
        $customThemesArray = Manager::getService('Themes')->getList($foContextFilters);
        $customThemesArray = $customThemesArray['data'];
        foreach ($customThemesArray as &$value) {
            $value['label'] = $value['text'];
            $themeInfosArray[] = $value;
        }

        $response = array();
        $response['total'] = count($themeInfosArray);
        $response['data'] = $themeInfosArray;
        $response['success'] = TRUE;
        $response['message'] = 'OK';

        return $response;
    }

    public function getThemeInfos($name)
    {
        $jsonFilePath = $this->getTemplateDir() . '/' . $name . '/theme.json';
        if (is_file($jsonFilePath)) {
            $themeJson = file_get_contents($jsonFilePath);
            $themeInfos = Json::decode($themeJson, Json::TYPE_ARRAY);
            return $themeInfos;
        } else {
            return null;
        }
    }

    public function getCurrentThemeInfos()
    {
        return $this->getThemeInfos($this->getCurrentTheme());
    }

    /**
     * Find a dam by its id
     *
     * @param string $damtId
     *            Contain the id of the requested dam
     */
    public static function getDam($damId)
    {
        $damService = Manager::getService("Dam");

        return $damService->findById($damId);
    }

    /**
     * Find a content by its id
     *
     * @param string $contentId
     *            Contain the id of the requested content
     */
    public static function getContent($contentId)
    {
        $contentService = Manager::getService("Contents");

        $return = $contentService->findById($contentId, contentId, true, false);
        return $return;
    }

    /**
     * Return true if the given page is in the current rootline
     *
     * @param string $pageId
     *            id of the page
     * @return boolean
     */
    public static function isInRootline($pageId)
    {
        return Manager::getService("Pages")->isInRootline($pageId);
    }

    /**
     * Get the media type
     */
    public static function getMediaType($mediaId)
    {
        return Manager::getService("Dam")->getMediaType($mediaId);
    }

    public static function mbucfirst($string)
    {
        $e = 'utf-8';
        if (function_exists('mb_strtoupper') && function_exists('mb_substr') && !empty($string)) {
            $string = mb_strtolower($string, $e);
            $upper = mb_strtoupper($string, $e);
            preg_match('#(.)#us', $upper, $matches);
            $string = $matches[1] . mb_substr($string, 1, mb_strlen($string, $e), $e);
        } else {
            $string = ucfirst($string);
        }
        return $string;
    }

    public function addExtensions(\Twig_Environment &$twig)
    {
        $twig->addExtension(new \Twig_Extension_Debug());

        $twig->addExtension(new BackOfficeTranslate());
        $twig->addExtension(new FrontOfficeTranslate());

        $twig->addExtension(new \Twig_Extensions_Extension_Intl());
        return $this;
    }

    public function addFilters(\Twig_Environment &$twig)
    {
        $twig->addFilter('cleanHtml', new \Twig_Filter_Function('\\Rubedo\\Templates\\FrontOfficeTemplates::cleanHtml', array(
            'is_safe' => array(
                'html'
            )
        )));
        $twig->addFilter(new \Twig_SimpleFilter('ucfirst', '\\Rubedo\\Templates\\FrontOfficeTemplates::mbucfirst'));
        $twig->addFilter(new \Twig_SimpleFilter('getCountryName', '\\Rubedo\\Templates\\FrontOfficeTemplates::getCountryName'));
        $twig->addFilter(new \Twig_SimpleFilter('ensureIsArray', function ($value) {
            return (array)$value;
        }));
        $twig->addFilter(new \Twig_SimpleFilter('ensureIsMultivalued', function ($value, $multiple = false) {
            if (is_array($value) && ($multiple || !is_numeric(key($value)))) {
                return array($value);
            }
            return (array)$value;
        }));
        $twig->addFilter(new \Twig_SimpleFilter('getClass', function ($value) {
            return get_class($value);
        }));
        return $this;
    }

    public function addFunctions(\Twig_Environment &$twig)
    {
        $twig->addFunction('url', new \Twig_Function_Function('\\Rubedo\\Templates\\FrontOfficeTemplates::url'));
        $twig->addFunction('displayUrl', new \Twig_Function_Function('\\Rubedo\\Templates\\FrontOfficeTemplates::displayUrl'));
        $twig->addFunction('getPageTitle', new \Twig_Function_Function('\\Rubedo\\Templates\\FrontOfficeTemplates::getPageTitle'));
        $twig->addFunction('getLinkedContents', new \Twig_Function_Function('\\Rubedo\\Templates\\FrontOfficeTemplates::getLinkedContents'));
        $twig->addFunction('getTaxonomyTerm', new \Twig_Function_Function('\\Rubedo\\Templates\\FrontOfficeTemplates::getTaxonomyTerm'));
        $twig->addFunction('getDam', new \Twig_Function_Function('\\Rubedo\\Templates\\FrontOfficeTemplates::getDam'));
        $twig->addFunction('getContent', new \Twig_Function_Function('\\Rubedo\\Templates\\FrontOfficeTemplates::getContent'));
        $twig->addFunction('isInRootline', new \Twig_Function_Function('\\Rubedo\\Templates\\FrontOfficeTemplates::isInRootline'));
        $twig->addFunction('getMediaType', new \Twig_Function_Function('\\Rubedo\\Templates\\FrontOfficeTemplates::getMediaType'));
        $twig->addFunction('imageUrl', new \Twig_Function_Function('\\Rubedo\\Templates\\FrontOfficeTemplates::imageUrl'));
        $twig->addFunction('mediaUrl', new \Twig_Function_Function('\\Rubedo\\Templates\\FrontOfficeTemplates::mediaUrl'));
        $twig->addFunction('mediaThumbnailUrl', new \Twig_Function_Function('\\Rubedo\\Templates\\FrontOfficeTemplates::mediaThumbnailUrl'));
        $twig->addFunction('staticUrl', new \Twig_Function_Function('\\Rubedo\\Templates\\FrontOfficeTemplates::staticUrl'));
        $twig->addFunction('flagUrl', new \Twig_Function_Function('\\Rubedo\\Templates\\FrontOfficeTemplates::flagUrl'));
        $twig->addFunction('userAvatar', new \Twig_Function_Function('\\Rubedo\\Templates\\FrontOfficeTemplates::userAvatar'));
        $twig->addFunction('userAvatarThumbnail', new \Twig_Function_Function('\\Rubedo\\Templates\\FrontOfficeTemplates::userAvatarThumbnail'));
        return $this;
    }

    /**
     * Read configuration from global application config and load it for the current class
     */
    public static function lazyloadConfig()
    {
        $config = Manager::getService('config');
        self::setConfig($config['templates']);
    }
}
