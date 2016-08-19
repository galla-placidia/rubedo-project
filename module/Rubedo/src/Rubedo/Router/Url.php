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
namespace Rubedo\Router;

use Rubedo\Exceptions\NotFound;
use Rubedo\Exceptions\Server;
use Rubedo\Interfaces\Router\IUrl;
use Rubedo\Services\Manager;
use Rubedo\Content\Context;
use Rubedo\Collection\AbstractCollection;
use Zend\Mvc\Router\RouteInterface;
use Rubedo\Collection\AbstractLocalizableCollection;
use Rubedo\Services\Events;

/**
 * Front Office URL service
 *
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
class Url implements IUrl
{

    /**
     * param delimiter
     */
    const PARAM_DELIMITER = '&';

    /**
     * URI delimiter
     */
    const URI_DELIMITER = '/';

    const PAGE_TO_URL_READ_CACHE_PRE = 'rubedo_page_to_url_cache_pre';

    const PAGE_TO_URL_READ_CACHE_POST = 'rubedo_page_to_url_cache_post';

    const URL_TO_PAGE_READ_CACHE_PRE = 'rubedo_url_to_page_cache_pre';

    const URL_TO_PAGE_READ_CACHE_POST = 'rubedo_url_to_page_cache_post';

    const MATCH_EXTRA_PARAMS = 'rubedo_match_extra_params';

    protected static $_disableNav = false;

    /**
     * MVC Router
     *
     * @var \Zend\Mvc\Router\RouteInterface
     */
    protected static $router = null;

    /**
     * current route name
     *
     * @var string
     */
    protected static $routeName = null;

    protected static $staticDomain = null;

    protected static $avatarUrls = array();

    /**
     * Number of URL segment match
     *
     * Shoud be equal to the total number of segment to have a complete match
     *
     * @var int
     */
    public $nbMatched = 0;

    /**
     * Extra parameters matched
     *
     * @var array
     */
    public $extraMatches = array();

    /**
     *
     * @return \Zend\Mvc\Router\RouteInterface
     */
    public function getRouter()
    {
        return Url::$router;
    }

    /**
     * Set the current Route
     *
     * @param \Zend\Mvc\Router\RouteInterface $route
     */
    public static function setRouter(RouteInterface $router)
    {
        Url::$router = $router;
    }

    /**
     *
     * @param string $routeName
     */
    public static function setRouteName($routeName)
    {
        Url::$routeName = $routeName;
    }

    /**
     * Return page id based on request URL
     *
     * @param string $url
     *            requested URL
     * @return string int
     */
    public function matchPageRoute($url, $host)
    {
        if (false !== strpos($url, '?')) {
            list ($url) = explode('?', $url);
        }
        $wasFiltered = AbstractCollection::disableUserFilter();
        $site = Manager::getService('Sites')->findByHost($host);
        AbstractCollection::disableUserFilter($wasFiltered);
        if (null == $site) {
            return null;
        }
        $locale = null;
        $siteId = $site['id'];

        $eventResult = Events::getEventManager()->trigger(self::URL_TO_PAGE_READ_CACHE_PRE, null, array(
            'url' => $url,
            'siteId' => $site['id']
        ));
        if ($eventResult->stopped()) {
            return $eventResult->last();
        }

        $urlSegments = explode(self::URI_DELIMITER, trim($url, self::URI_DELIMITER));

        // check for locale in URL
        if (!empty($urlSegments[0])) {
            $language = Manager::getService('Languages')->findActiveByLocale($urlSegments[0]);
            if ($language && in_array($language['locale'], $site['languages'])) {
                array_shift($urlSegments);
                $locale = $language['locale'];
            }
        }

        $lastMatchedNode = 'root';
        if (empty($urlSegments[0])) {
            if (isset($site['homePage'])) {
                return array(
                    'pageId' => $site['homePage'],
                    'locale' => $locale
                );
            } else {
                return null;
            }
        }

        $nbSegments = count($urlSegments);
        $this->nbMatched = 0;

        foreach ($urlSegments as $value) {
            $wasFiltered = AbstractCollection::disableUserFilter();
            $matchedNode = Manager::getService('Pages')->matchSegment($value, $lastMatchedNode, $siteId);
            AbstractCollection::disableUserFilter($wasFiltered);

            if (null === $matchedNode) {
                break;
            } else {
                $lastMatchedNode = $matchedNode['id'];
            }
            $this->nbMatched++;
        }

        if ($this->nbMatched == 0) {
            if (isset($site['defaultNotFound'])&&$site['defaultNotFound']!="") {
                return array(
                    'pageId' => $site['defaultNotFound'],
                    'locale' => $locale
                );
            }
            return null;
        }
        $contentId = null;

        // next segment could be a content-id
        if ($nbSegments > $this->nbMatched) {
            $urlSegments = array_slice($urlSegments, $this->nbMatched);
            if (count($urlSegments) > 1) {
                $segment = array_slice($urlSegments, 0, 2);
                $contentId = $this->matchContentRoute($segment, $locale);
                if ($contentId) {
                    array_slice($urlSegments, 2);
                    $this->nbMatched = $this->nbMatched + 2;
                }
            }
        }

        // trigger an event to allow more matches from specific rules
        if ($nbSegments > $this->nbMatched) {
            Events::getEventManager()->trigger(self::MATCH_EXTRA_PARAMS, $this, array(
                'urlSegments' => $urlSegments
            ));
        }

        if ($nbSegments > $this->nbMatched) {
            if (isset($site['defaultNotFound'])&&$site['defaultNotFound']!="") {
                return array(
                    'pageId' => $site['defaultNotFound'],
                    'locale' => $locale
                );
            }
            return null;
        }

        $result = array(
            'pageId' => $lastMatchedNode,
            'url' => $url,
            'siteId' => $siteId,
            'locale' => $locale
        );

        if ($contentId) {
            $result['content-id'] = $contentId;
        }
        $result = array_merge($result, $this->extraMatches);
        Events::getEventManager()->trigger(self::URL_TO_PAGE_READ_CACHE_POST, null, $result);
        unset($result['siteId']);
        unset($result['url']);
        return $result;
    }

    public function disableNavigation()
    {
        self::$_disableNav = true;
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Rubedo\Interfaces\Router\IUrl::getPageUrl()
     */
    public function getPageUrl($pageId, $locale)
    {
        if (self::$_disableNav) {
            return trim('#', self::URI_DELIMITER);
        }
        $eventResult = Events::getEventManager()->trigger(self::PAGE_TO_URL_READ_CACHE_PRE, null, array(
            'pageId' => $pageId,
            'locale' => $locale
        ));
        if ($eventResult->stopped()) {
            return $eventResult->last();
        }

        $url = '';
        if ($locale) {
            $url .= self::URI_DELIMITER;
            $url .= $locale;
        }

        $wasWithI18n = AbstractLocalizableCollection::getIncludeI18n();
        AbstractLocalizableCollection::setIncludeI18n(true);

        $page = Manager::getService('Pages')->findById($pageId);

        $siteId = $page['site'];
        $site = Manager::getService('sites')->findById($siteId);
        if ($site['locStrategy'] == 'fallback') {
            $fallbackLocale = $site['defaultLanguage'];
        } else {
            $fallbackLocale = null;
        }

        $rootline = Manager::getService('Pages')->getAncestors($page);

        foreach ($rootline as $value) {
            if ($locale && isset($value['i18n'][$locale]['pageURL'])) {
                $url .= self::URI_DELIMITER;
                $url .= urlencode($value['i18n'][$locale]['pageURL']);
            } elseif ($fallbackLocale && isset($value['i18n'][$fallbackLocale]['pageURL'])) {
                $url .= self::URI_DELIMITER;
                $url .= urlencode($value['i18n'][$fallbackLocale]['pageURL']);
            } else {
                return false;
            }
        }

        if ($locale && isset($page['i18n'][$locale])) {
            $url .= self::URI_DELIMITER;
            $url .= urlencode($page['i18n'][$locale]['pageURL']);
        } elseif ($fallbackLocale && isset($page['i18n'][$fallbackLocale])) {
            $url .= self::URI_DELIMITER;
            $url .= urlencode($page['i18n'][$fallbackLocale]['pageURL']);
        } elseif (!isset($page['i18n'])) {
            $url .= self::URI_DELIMITER;
            $url .= urlencode($page['pageURL']);
        } else {
            return false;
        }

        AbstractLocalizableCollection::setIncludeI18n($wasWithI18n);

        $urlToCache = array(
            'pageId' => $pageId,
            'url' => $url,
            'siteId' => $siteId,
            'locale' => $locale
        );
        $eventResult = Events::getEventManager()->trigger(self::PAGE_TO_URL_READ_CACHE_POST, null, $urlToCache);

        return $url;
    }

    public function getContentUrl($contentId, $locale, $fallbackLocale = null)
    {
        $url = '';
        $wasWithI18n = AbstractLocalizableCollection::getIncludeI18n();
        AbstractLocalizableCollection::setIncludeI18n(true);
        $wasFiltered = AbstractCollection::disableUserFilter();
        $content = Manager::getService('Contents')->findById($contentId, true, false);
        AbstractCollection::disableUserFilter($wasFiltered);
        AbstractLocalizableCollection::setIncludeI18n($wasWithI18n);
        if ($content) {
            if (isset($content['i18n'][$locale]['fields']['urlSegment']) && !empty($content['i18n'][$locale]['fields']['urlSegment'])) {
                $url .= self::URI_DELIMITER;
                $url .= (string)$content['id'];
                $url .= self::URI_DELIMITER;
                $url .= $this->slugify((string)$content['i18n'][$locale]['fields']['urlSegment']);
            } elseif ($fallbackLocale && isset($content['i18n'][$fallbackLocale]['fields']['urlSegment']) && !empty($content['i18n'][$fallbackLocale]['fields']['urlSegment'])) {
                $url .= self::URI_DELIMITER;
                $url .= (string)$content['id'];
                $url .= self::URI_DELIMITER;
                $url .= $this->slugify((string)$content['i18n'][$fallbackLocale]['fields']['urlSegment']);
            } elseif (isset($content['i18n'][$locale]['fields']['text'])) {
                $url .= self::URI_DELIMITER;
                $url .= (string)$content['id'];
                $url .= self::URI_DELIMITER;
                $url .= $this->slugify((string)$content['i18n'][$locale]['fields']['text']);
            } elseif ($fallbackLocale && isset($content['i18n'][$fallbackLocale]['fields']['text'])) {
                $url .= self::URI_DELIMITER;
                $url .= (string)$content['id'];
                $url .= self::URI_DELIMITER;
                $url .= $this->slugify((string)$content['i18n'][$fallbackLocale]['fields']['text']);
            }
        }
        if (!empty($url)) {
            return $url;
        } else {
            return false;
        }
    }

    public function matchContentRoute($segments, $locale)
    {
        if (!$locale) {
            return null;
        }

        list ($contentId, $encodedText) = $segments;
        unset($encodedText);

        $wasWithI18n = AbstractLocalizableCollection::getIncludeI18n();
        AbstractLocalizableCollection::setIncludeI18n(true);
        $wasFiltered = AbstractCollection::disableUserFilter();
        $content = Manager::getService('Contents')->findById($contentId, true, false);
        AbstractCollection::disableUserFilter($wasFiltered);
        AbstractLocalizableCollection::setIncludeI18n($wasWithI18n);
        if (!$content) {
            return null;
        } elseif ((isset($content['online'])) && ($content['online'] === false)) {
            return null;
        } else {
            return $contentId;
        }
    }

    /**
     * (non-PHPdoc)
     *
     * @see \Rubedo\Interfaces\Router\IUrl::getUrl()
     */
    public function getUrl($data, $encode = false)
    {
        if (self::$_disableNav) {
            return trim('#', self::URI_DELIMITER);
        }

        if (!isset($data['pageId'])) {
            return null;
        }

        $url = $this->getPageUrl($data['pageId'], $data['locale']);

        if ($url == false) {
            return false;
        }

        if (isset($data['content-id'])) {
            $page = Manager::getService('Pages')->findById($data['pageId']);

            $siteId = $page['site'];
            $site = Manager::getService('sites')->findById($siteId);
            if ($site['locStrategy'] == 'fallback') {
                $fallbackLocale = $site['defaultLanguage'];
            } else {
                $fallbackLocale = null;
            }
            $contentPart = $this->getContentUrl($data['content-id'], $data['locale'], $fallbackLocale);
            if (!$contentPart) {
                return false;
            } else {
                $url .= $contentPart;
            }
        }

        return '/' . ltrim($url, self::URI_DELIMITER);
    }

    /**
     * Generates an url given the name of a route.
     *
     * @access public
     * @param array $urlOptions
     *            Options passed to the assemble method of the
     *            Route object.
     * @param mixed $name
     *            The name of a Route to use. If null it will use the
     *            current Route
     * @param bool $reset
     *            Whether or not to reset the route defaults with those
     *            provided
     * @return string Url for the link href attribute.
     * @todo handle URL prefix
     */
    public function url(array $urlOptions = array(), $name = null, $reset = false, $encode = true)
    {
        $options = array(
            'encode' => $encode,
            'reset' => $reset,
            'name' => self::$routeName
        );
        if ($name) {
            $options['name'] = $name;
        }
        $params = array();
        $mergedParams = array();
        foreach ($urlOptions as $key => $value) {
            switch ($key) {
                case 'pageId':
                case 'controller':
                case 'action':
                case 'content-id':
                case 'locale':
                    $params[$key] = $value;
                    break;
                case 'module':
                    break;
                default:
                    $mergedParams[$key] = $value;
            }
        }
        if (!isset($params['locale'])) {
            $params['locale'] = AbstractLocalizableCollection::getWorkingLocale();
        }
        $uri = Manager::getService('Application')->getRequest()->getUri();

        switch ($options['reset']) {
            case 'true':
                break;
            case 'add':
                $currentParams = $uri->getQueryAsArray();
                foreach ($mergedParams as $key => $value) {

                    if (!isset($currentParams[$key])) {
                        $currentParams[$key] = array();
                    }
                    if (!is_array($value)) {
                        $value = array(
                            $value
                        );
                    }
                    $mergedParams[$key] = array_unique(array_merge($currentParams[$key], $value));
                }
                $mergedParams = array_merge($currentParams, $mergedParams);
                break;
            case 'sub':
                $currentParams = $uri->getQueryAsArray();
                foreach ($mergedParams as $key => $value) {
                    if ($key == 'pageId') {
                        continue;
                    }
                    if (!isset($currentParams[$key])) {
                        $currentParams[$key] = array();
                    } elseif (!is_array($currentParams[$key])) {
                        $currentParams[$key] = array(
                            $currentParams[$key]
                        );
                    }
                    if (!is_array($value)) {
                        $value = array(
                            $value
                        );
                    }
                    $mergedParams[$key] = array_diff($currentParams[$key], $value);
                }
                $mergedParams = array_merge($currentParams, $mergedParams);
                break;
            default:
                $mergedParams = array_merge($uri->getQueryAsArray(), $mergedParams);
                break;
        }
        // prevent empty values to propagate through URL
        foreach ($mergedParams as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subkey => $subvalue) {
                    if (empty($subvalue)) {
                        unset($mergedParams[$key][$subkey]);
                    }
                }
                if (count($mergedParams[$key]) == 0) {
                    unset($mergedParams[$key]);
                } else {
                    $mergedParams[$key] = array_values($mergedParams[$key]);
                }
            } elseif (empty($value)) {
                unset($mergedParams[$key]);
            }
        }

        $options['query'] = $mergedParams;
        return $this->getRouter()->assemble($params, $options);
    }

    /**
     * Return the url of the single content page of the site if the single page
     * exist
     *
     * @param string $contentId
     *            Id of the content to display
     * @param string $type
     *            Type of the URL : "default" or "cononical"
     * @param string $siteId
     *            Id of the site
     *
     * @return string Url
     */
    public function displayUrl($contentId, $type = "default", $siteId = null, $defaultPage = null)
    {
        if (self::$_disableNav) {
            return trim('#', self::URI_DELIMITER);
        }
        $pageValid = false;
        if ($siteId === null) {
            $doNotAddSite = true;
            $siteId = Manager::getService('PageContent')->getCurrentSite();
        } else {
            $doNotAddSite = false;
        }

        $ws = Context::isDraft() ? 'draft' : 'live';

        $content = Manager::getService('Contents')->findById($contentId, $ws === 'live', false);

        if (isset($content['taxonomy']['navigation']) && $content['taxonomy']['navigation'] !== "") {
            foreach ($content['taxonomy']['navigation'] as $pageId) {
                if ($pageId == 'all') {
                    continue;
                }
                $page = Manager::getService('Pages')->findById($pageId);
                if ($page && $page['site'] == $siteId) {
                    $pageValid = true;
                    break;
                }
            }
        }

        if (!$pageValid) {
            if ($type == "default") {
                if ($defaultPage) {
                    $pageId = $defaultPage;
                } else {
                    $pageId = Manager::getService('PageContent')->getCurrentPage();
                    $page = Manager::getService('Pages')->findById($pageId);
                    if (isset($page['maskId'])) {
                        $mask = Manager::getService('Masks')->findById($page['maskId']);
                        if (!isset($mask['mainColumnId']) || empty($mask['mainColumnId'])) {
                            $pageId = $this->_getDefaultSingleBySiteID($siteId);
                        }
                    }
                }
            } elseif ($type == "canonical") {
                $pageId = $this->_getDefaultSingleBySiteID($siteId);
            } else {
                throw new Server("You must specify a good type of URL : default or canonical", "Exception94");
            }
        }

        if ($pageId) {
            $data = array(
                'pageId' => $pageId,
                'content-id' => $contentId
            );

            if ($type == "default") {
                $pageUrl = $this->url($data, 'rewrite', true);
            } elseif ($type == "canonical") {
                // @todo refactor this
                $pageUrl = $this->url($data, null, true);
            } else {
                throw new Server("You must specify a good type of URL : default or canonical", "Exception94");
            }

            if ($doNotAddSite) {
                return $pageUrl;
            } else {

                return 'http://' . Manager::getService('Sites')->getHost($siteId) . $pageUrl;
            }
        } else {
            return '#';
        }
    }

    protected function _getDefaultSingleBySiteID($siteId)
    {
        $site = Manager::getService('Sites')->findById($siteId);
        if (isset($site['defaultSingle'])) {
            if (Manager::getService('Pages')->findById($site['defaultSingle'])) {
                return $site['defaultSingle'];
            } else {
                return null;
            }
        } else {
            return null;
        }
    }

    public function mediaUrl($mediaId, $forceDownload = null)
    {
        $damService = Manager::getService('Dam');
        $media = $damService->findById($mediaId);
        $isPublic = $damService->isPublic($mediaId);
        if (!$media) {
            return '';
        }
        $fileService = Manager::getService('Files');
        $obj = $fileService->findById($media['originalFileId']);
        if (!$obj) {
            return '';
        }
        $meta = $obj->file;

        $router = Manager::getService('Router');
        $options = array(
            'name' => 'mediaFromDam'
        );
        $params = array(
            'version' => !empty($meta['version']) ? $meta['version'] : 1,
            'download' => $forceDownload ? 'download' : 'inline',
            'mediaId' => $mediaId,
            'filename' => $meta['filename']
        );
        $url = $router->assemble($params, $options);
        if ($isPublic) {
            $url = $this->staticUrl($url);
        }
        return $url;
    }

    public function mediaThumbnailUrl($mediaId)
    {
        $url = '/dam/get-thumbnail?media-id=' . $mediaId;
        $damService = Manager::getService('Dam');
        $isPublic = $damService->isPublic($mediaId);
        if ($isPublic) {
            $url = $this->staticUrl($url);
        }
        return $url;
    }

    public function imageUrl($mediaId, $width = null, $height = null, $mode = 'crop')
    {
        $damService = Manager::getService('Dam');
        $media = $damService->findById($mediaId);
        $isPublic = $damService->isPublic($mediaId);
        if (!$media) {
            return '';
        }
        $fileService = Manager::getService('Files');
        $obj = $fileService->findById($media['originalFileId']);
        if (!$obj) {
            return '';
        }
        $meta = $obj->file;

        $router = Manager::getService('Router');
        $options = array(
            'name' => 'imageFromDam'
        );
        $params = array(
            'version' => $meta['version'],
            'width' => $width ? $width : 'x',
            'height' => $height ? $height : 'x',
            'mode' => $mode ? $mode : 'crop',
            'mediaId' => $mediaId,
            'filename' => $meta['filename']
        );
        $url = $router->assemble($params, $options);
        if ($isPublic) {
            $url = $this->staticUrl($url);
        }
        return $url;
    }

    public function staticUrl($url)
    {
        if (!isset(self::$staticDomain)) {
            $siteId = Manager::getService('PageContent')->getCurrentSite();
            if ($siteId) {
                $site = Manager::getService('Sites')->findById($siteId);
                if ($site) {
                    if (isset($site['staticDomain'])) {
                        self::$staticDomain = $site['staticDomain'] ? $site['staticDomain'] : '';
                    }
                }
            }
            if (!isset(self::$staticDomain)) {
                self::$staticDomain = '';
            }
        }
        if (self::$staticDomain === '') {
            return $url;
        } else {
            return 'http://' . self::$staticDomain . '/' . ltrim($url, '/');
        }
    }

    public function flagUrl($code, $size = 16)
    {
        if (!in_array($size, array(
            16,
            24,
            32,
            48,
            64
        ))
        ) {
            $size = 16;
        }
        $url = "/assets/flags/$size/$code.png";
        return $this->staticUrl($url);
    }

    /**
     * Get the url for user's avatar
     *
     * @param $userId
     * @param int $width
     * @param int $height
     * @param string $mode crop|morph
     * @return mixed
     * @throws NotFound
     */
    public function userAvatar($userId, $width = null, $height = null, $mode = 'morph')
    {
        if (!isset(self::$avatarUrls[$userId])) {
            $user = Manager::getService('Users')->findById($userId);
            if (!$user || !isset($user['photo']) || empty($user['photo'])) {
                return " ";
            }
            $fileId = $user['photo'];
            $fileService = Manager::getService('Images');
            $obj = $fileService->findById($fileId);
            if (!$obj instanceof \MongoGridFSFile) {
                return " ";
            }
            $meta = $obj->file;
            $params = array(
                'filename' => $meta['filename'],
                'version' => $meta['version'],
                'userId' => $userId,
                'width' => $width,
                'height' => $height,
                'mode' => $mode,
            );

            $options = array(
                'name' => 'avatar',
            );

            $router = Manager::getService('Router');
            $url = $router->assemble($params, $options);
            $url = $this->staticUrl($url);
            self::$avatarUrls[$userId] = $url;
        }
        return self::$avatarUrls[$userId];
    }

    public function userAPIAvatar($user = array(), $width = null, $height = null, $mode = 'morph')
    {
        if (!isset($user) || !is_array($user)) {
            return " ";
        }
        $userId = $user['id'];
        if (!isset(self::$avatarUrls[$userId])) {
            if (!$user || !isset($user['photo']) || empty($user['photo'])) {
                return " ";
            }
            $fileId = $user['photo'];
            $fileService = Manager::getService('Images');
            $obj = $fileService->findById($fileId);
            if (!$obj instanceof \MongoGridFSFile) {
                return " ";
            }
            $meta = $obj->file;
            $params = array(
                'filename' => $meta['filename'],
                'version' => $meta['version'],
                'userId' => $userId,
                'width' => $width,
                'height' => $height,
                'mode' => $mode,
            );

            $options = array(
                'name' => 'avatar',
            );

            $router = Manager::getService('Router');
            $url = $router->assemble($params, $options);
            $url = $this->staticUrl($url);
            self::$avatarUrls[$userId] = $url;
        }
        return self::$avatarUrls[$userId];
    }

    public function slugify($fragmentToSlug)
    {
        $unwanted_array = array( 'Š'=>'S', 'š'=>'s', 'Ž'=>'Z', 'ž'=>'z', 'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C', 'È'=>'E', 'É'=>'E',
            'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O', 'Ù'=>'U',
            'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'B', 'ß'=>'Ss', 'à'=>'a', 'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c',
            'è'=>'e', 'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'o', 'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o',
            'ö'=>'o', 'ő' => 'o', 'ø'=>'o', 'ù'=>'u', 'ú'=>'u', 'û'=>'u', 'ü' => 'u', 'ý'=>'y', 'þ'=>'b', 'ÿ'=>'y' , '%'=>'');
        $clean = strtr($fragmentToSlug, $unwanted_array);
        $clean = strtolower(trim($clean, '-'));
        $clean = preg_replace('/[\/_|+ -\']+/', '-', $clean);

        return $clean;
    }
}
