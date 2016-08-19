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
namespace Rubedo\Internationalization;

use Rubedo\Services\Manager;
use Rubedo\Interfaces\Internationalization\ICurrent;
use Rubedo\Collection\AbstractLocalizableCollection;

/**
 * Determine current localization
 *
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
class Current implements ICurrent
{

    public function resolveLocalization($siteId = null, $forceLocal = null, $cookieValue = null)
    {
        $locale = null;
        $site = null;
        $browserArray = Manager::getService('CurrentLocalization')->getBrowserLanguages();
        if ($siteId) {
            $site = Manager::getService('Sites')->findById($siteId);
            if (!isset($site['languages']) || !is_array($site['languages'])) {
                $site['languages'] = array();
            }
        }

        if ($site) {
            $user = Manager::getService('CurrentUser')->getCurrentUser();

            if ($forceLocal && in_array($forceLocal, $site['languages'])) {
                // if locale is forced through URL
                $locale = $forceLocal;
            } elseif ($user && isset($user['preferedLanguage']) && isset($user['preferedLanguage'][$siteId]) && in_array($user['preferedLanguage'][$siteId], $site['languages'])) {
                // if prefered locale is known for current user
                $locale = $user['preferedLanguage'][$siteId];
            } elseif (!$user && isset($cookieValue) && in_array($cookieValue, $site['languages'])) {
                // fetch current locale from cookie
                $locale = $cookieValue;
            } else {
                // default strategy
                if (isset($site['useBrowserLanguage']) && $site['useBrowserLanguage'] == true) {
                    // use browser settings
                    $locale = $this->findBestMatchForBrowser($site['languages'], $browserArray);
                    if (!$locale) {
                        // fallback to default
                        $locale = $site['defaultLanguage'];
                    }
                } else {
                    // use default
                    $locale = $site['defaultLanguage'];
                }
                if (!isset($locale)) {
                    // if nothing works, use english
                    $locale = 'en';
                }
            }

            AbstractLocalizableCollection::setLocalizationStrategy($site['locStrategy']);
            if ($site['locStrategy'] == 'fallback') {
                AbstractLocalizableCollection::setFallbackLocale($site['defaultLanguage']);
            }

            if ($user) {
                if (!isset($user['preferedLanguage'][$siteId]) || $user['preferedLanguage'][$siteId] != $locale) {
                    $user['preferedLanguage'][$siteId] = $locale;
                    Manager::getService('Users')->update($user);
                }
            }
        } else {
            $locale = 'en';
        }

        // set the collection with locale as working language
        AbstractLocalizableCollection::setWorkingLocale($locale);
        AbstractLocalizableCollection::setIncludeI18n(false);

        return $locale;
    }

    /**
     * find first match of site language for given user preferences through browser
     *
     * @param array $languages
     * @param array $browserArray
     * @return string NULL
     */
    protected function findBestMatchForBrowser($languages, $browserArray)
    {
        foreach ($browserArray as $locale) {
            if (in_array($locale, $languages)) {
                return $locale;
            }
        }
        return null;
    }

    public function getCurrentLocalization()
    {
        return AbstractLocalizableCollection::getWorkingLocale();
    }

    public function getBrowserLanguages()
    {
        // init browser languages
        $browserLanguages = array();
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            $httpValue = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
            if (!empty($httpValue)) {
                $languages = explode(',', $httpValue);
                foreach ($languages as $language) {
                    list ($locale) = explode(';', $language);

                    $browserLanguages[] = $locale;
                }
            }
            if (empty($browserLanguages)) {
                $browserLanguages = array(
                    'en'
                );
            }
        }

        return $browserLanguages;
    }
}