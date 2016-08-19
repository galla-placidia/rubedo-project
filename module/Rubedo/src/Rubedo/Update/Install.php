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

use Rubedo\Services\Manager;
use WebTales\MongoFilters\Filter;
use Rubedo;
use Zend\Json\Json;
use Rubedo\Services\Events;

/**
 * Methods
 * for
 * install
 * tool
 *
 * @author jbourdin
 *
 */
class Install
{

    const SAVECONFIG = 'install_saveconfig';

    protected static $translateService;

    protected $configFilePath;

    protected $configDirPath;

    protected $localConfig = array();

    public function __construct()
    {
        $this->configDirPath = realpath(APPLICATION_PATH . '/config/autoload/');
        $this->configFilePath = $this->configDirPath . '/local.php';
        Events::getEventManager()->attach(Install::SAVECONFIG, array(
            $this,
            'clearConfigCache'
        ));
    }

    public function isConfigWritable()
    {
        if (is_file($this->configFilePath)) {
            return is_writable($this->configFilePath);
        } else {
            return is_writable($this->configDirPath);
        }
    }

    public function getConfigFilePath()
    {
        return $this->configFilePath;
    }

    public function saveLocalConfig($config = null,$persistToDB = false)
    {
        if ($config) {
            $this->setLocalConfig($config);
        }
        $configContent = "<?php \n return " . var_export($this->getLocalConfig(), true) . ";";
        file_put_contents($this->configFilePath, $configContent, LOCK_EX);
        if (function_exists('accelerator_reset')) { // As config is a php file, we should reset bytecode cache to have new configuration
            accelerator_reset();
        }
        if (function_exists("opcache_invalidate")) {
            opcache_invalidate($this->configFilePath, true);
        }
        //save to DB
        if($persistToDB&&isset($config["datastream"]["mongo"]["server"])){
            Manager::getService("MongoConf")->setRubedoConf($config);
        }
        //tell all nodes to reload if PHP cluster
        Events::getEventManager()->trigger(static::SAVECONFIG);
        if($persistToDB&&isset($config["datastream"]["mongo"]["server"])&&isset($config["webCluster"])&&is_array($config["webCluster"])){
            foreach($config["webCluster"] as $clusterHost){
                $curlUrl = "http://" . $clusterHost . "/api/v1/reloadconfig";
                $curly = curl_init();
                curl_setopt($curly, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($curly, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curly, CURLOPT_AUTOREFERER, true);
                curl_setopt($curly, CURLOPT_URL, $curlUrl);
                curl_setopt($curly, CURLOPT_CUSTOMREQUEST, "POST");
                $curlyresult=curl_exec($curly);
                curl_close($curly);
            }
        }
    }

    public function loadLocalConfig()
    {
        if (is_file($this->configFilePath)) {
            $this->localConfig = require $this->configFilePath;
        }
    }

    public function getLocalConfig()
    {
        return $this->localConfig;
    }

    public function setLocalConfig(array $config)
    {
        $this->localConfig = $config;
    }

    public static function doInsertContents()
    {
        $defaultLocale = Manager::getService('Languages')->getDefaultLanguage();
        if (!$defaultLocale) {
            return false;
        }
        \Rubedo\Internationalization\Translate::setDefaultLanguage($defaultLocale);

        //$translateService = Manager::getService('Translate');

        $success = true;

        $contentPath = APPLICATION_PATH . '/data/default/';
        $contentIterator = new \DirectoryIterator($contentPath);
        foreach ($contentIterator as $directory) {
            if ($directory->isDot() || !$directory->isDir()) {
                continue;
            }
            if (in_array($directory->getFilename(), array(
                'groups',
                'site'
            ))) {
                continue;
            }
            $collection = ucfirst($directory->getFilename());
            $collectionService = Manager::getService($collection);
            //$isLocalizable = $collectionService instanceof Rubedo\Collection\AbstractLocalizableCollection;
            $itemsJson = new \DirectoryIterator($contentPath . '/' . $directory->getFilename());
            foreach ($itemsJson as $file) {
                if ($file->isDot() || $file->isDir()) {
                    continue;
                }
                if ($file->getExtension() == 'json') {
                    $itemJson = file_get_contents($file->getPathname());

                    $itemJson = preg_replace_callback('/###(.*)###/U', array(
                        'Rubedo\\Update\\Install',
                        'replaceWithTranslation'
                    ), $itemJson);

                    $item = Json::decode($itemJson, Json::TYPE_ARRAY);

                    try {
                        if (!$collectionService->findOne(Filter::factory('Value')->setName('defaultId')
                            ->setValue($item['defaultId']))
                        ) {
                            if (isset($item['defaultId'])){
                            	try {
                            	    $item["_id"] = new \MongoId($item['defaultId']);
                            	} catch(\Exception $e) {
                            	    throw new \Rubedo\Exceptions\Server('You tried to insert a bad MongoId in database: ' . $item['defaultId']);
                            	}
                                $item["_id"]=new \MongoId($item['defaultId']);
                            }
                            $result = $collectionService->create($item);
                        } else {
                            $result['success'] = true;
                        }
                    } catch (\Rubedo\Exceptions\User $exception) {
                        $result['success'] = true;
                    }

                    $success = $result['success'] && $success;
                }
            }
        }
        return $success;
    }

    public static function replaceWithTranslation($matches)
    {
        if (is_null(self::$translateService)) {
            $defaultLocale = Manager::getService('Languages')->getDefaultLanguage();
            \Rubedo\Internationalization\Translate::setDefaultLanguage($defaultLocale);

            self::$translateService = Manager::getService('Translate');
        }
        if ($matches[1] == 'Locale') {
            return \Rubedo\Internationalization\Translate::getDefaultLanguage();
        }
        $result = self::$translateService->translate($matches[1]);
        if (empty($result)) {
            throw new \Rubedo\Exceptions\Server('can\'t translate :' . $matches[1]);
        }
        return $result;
    }

    public function doCreateDefaultsGroups()
    {
        $defaultLocale = Manager::getService('Languages')->getDefaultLanguage();
        if (!$defaultLocale) {
            return false;
        }
        \Rubedo\Internationalization\Translate::setDefaultLanguage($defaultLocale);

        try {
            $adminWorkspaceId = Manager::getService('Workspaces')->getAdminWorkspaceId();
            if (!$adminWorkspaceId) {
                Manager::getService('Workspaces')->create(array(
                    'text' => Manager::getService('Translate')->translate("Workspace.admin", 'admin'),
                    'nativeLanguage' => $defaultLocale
                ));
            }
        } catch (Rubedo\Exceptions\User $exception) {
            // dont
            // stop
            // if
            // already
            // exists
        }
        $adminWorkspaceId = Manager::getService('Workspaces')->getAdminWorkspaceId();

        $success = true;
        $groupsJsonPath = APPLICATION_PATH . '/data/default/groups';
        $groupsJson = new \DirectoryIterator($groupsJsonPath);
        foreach ($groupsJson as $file) {
            if ($file->isDot() || $file->isDir()) {
                continue;
            }
            if ($file->getExtension() == 'json') {
                $itemJson = file_get_contents($file->getPathname());

                $itemJson = preg_replace_callback('/###(.*)###/U', array(
                    'Rubedo\\Update\\Install',
                    'replaceWithTranslation'
                ), $itemJson);

                $item = Json::decode($itemJson, Json::TYPE_ARRAY);

                if ($item['name'] == 'admin') {
                    $item['workspace'] = $adminWorkspaceId;
                    $item['inheritWorkspace'] = false;
                }
                $result = Manager::getService('Groups')->create($item);
                $success = $result['success'] && $success;
            }
        }

        return $success;
    }

    public static function setDbVersion($version)
    {
        Manager::getService('RubedoVersion')->setDbVersion($version);
    }

    /**
     * Import
     * in
     * languages
     * collection
     * all
     * languages
     * form
     * iso-639
     */
    public static function importLanguages()
    {
        $tsvFile = APPLICATION_PATH . '/data/ISO-639-2_utf-8.txt';
        $file = fopen($tsvFile, 'r');
        $service = Manager::getService('Languages');
        while ($line = fgetcsv($file, null, '|')) {
            if (empty($line[2])) {
                continue;
            }
            $lang = array();
            $lang['iso2'] = $line[2];
            $lang['locale'] = $line[2];
            $lang['iso3'] = $line[0];
            $lang['label'] = $line[3];
            $lang['labelFr'] = $line[4];

            $upsertFilter = Filter::factory('Value')->setName('locale')->setValue($lang['locale']);
            $service->create($lang, array(
                'upsert' => $upsertFilter
            ));
        }
        return true;
    }

    /**
     * Set
     * a
     * language
     * as
     * default
     * language
     *
     * @param string $locale
     * @return boolean
     */
    public function setDefaultRubedoLanguage($locale)
    {
        $service = Manager::getService('Languages');

        $options = array(
            'multiple' => true
        );

        // ensure
        // only
        // one
        // default
        // exist
        $data = array(
            '$set' => array(
                'isDefault' => false
            )
        );
        $service->customUpdate($data, Filter::factory(), $options);

        // set
        // selected
        // language
        // to
        // active
        // and
        // default
        $data = array(
            '$set' => array(
                'isDefault' => true,
                'active' => true
            )
        );
        $filter = Filter::factory('Value')->setName('locale')->setValue($locale);
        $service->customUpdate($data, $filter, $options);

        // set
        // default
        // language
        // for
        // existing
        // sites
        $data = array(
            '$set' => array(
                'locStrategy' => 'onlyOne',
                'defaultLanguage' => $locale,
                'languages' => array(
                    $locale
                )
            )
        );
        $updateCond = Filter::factory('OperatorToValue')->setName('locStrategy')
            ->setOperator('$exists')
            ->setValue(false);
        $options = array(
            'multiple' => true
        );
        Manager::getService('Sites')->customUpdate($data, $updateCond, $options);

        // set
        // default
        // working
        // language
        // for
        // BO
        // for
        // users
        $data = array(
            '$set' => array(
                'workingLanguage' => $locale
            )
        );
        $updateCond = Filter::factory('OperatorToValue')->setName('workingLanguage')
            ->setOperator('$exists')
            ->setValue(false);
        $options = array(
            'multiple' => true
        );
        Manager::getService('Users')->customUpdate($data, $updateCond, $options);

        // ensure that localizable collections are now localized
        \Rubedo\Collection\AbstractLocalizableCollection::localizeAllCollection();

        return true;
    }

    public function doEnsureIndexes()
    {
        Manager::getService('UrlCache')->drop();
        Manager::getService('Cache')->drop();
        Manager::getService('ApiCache')->drop();
        Manager::getService('ApiCache')->ensureIndexes();
        $servicesArray = \Rubedo\Interfaces\config::getCollectionServices();
        $result = true;
        foreach ($servicesArray as $service) {
            if (!Manager::getService($service)->checkIndexes()) {
            	Manager::getService($service)->dropIndexes();
                $result = $result && Manager::getService($service)->ensureIndexes();
            }
        }
        if ($result) {
            $this->localConfig['installed']['index'] = $this->localConfig["datastream"]["mongo"]["server"] . '/' . $this->localConfig["datastream"]["mongo"]['db'];
            return true;
        } else {
            $this->viewData->hasError = true;
            $this->viewData->errorMsgs = 'failed to apply indexes';
            return false;
        }
    }

    public function isDefaultGroupsExists()
    {
        $adminGroup = Manager::getService('Groups')->findByName('admin');
        $publicGroup = Manager::getService('Groups')->findByName('public');
        $result = !is_null($adminGroup) && !is_null($publicGroup);
        return $result;
    }

    public function clearConfigCache()
    {
        $moduleConfigCachePath = CONFIG_CACHE_DIR . '/module-config-cache..php';
        if (is_file($moduleConfigCachePath)) {
            unlink($moduleConfigCachePath);
        }
    }

    public function clearFileCaches()
    {
        $paths = array(
            APPLICATION_PATH . '/public/generate-image',
            APPLICATION_PATH . '/public/theme',
            APPLICATION_PATH . '/cache/images',
            APPLICATION_PATH . '/cache/twig',
            APPLICATION_PATH . '/cache/config',
            APPLICATION_PATH . '/cache/htmlpurifier'
        );
        foreach ($paths as $path) {
            $this->deletedFolderContent($path);
        }
    }

    protected function deletedFolderContent($path)
    {
        if (!is_dir($path)) {
            return;
        }
        $iterator = new \DirectoryIterator($path);
        foreach ($iterator as $item) {
            if ($item->isDot()) {
                continue;
            }
            if ($item->isDir()) {
                $this->deletedFolderContent($item->getPathname());
                if ($item->isWritable()) {
                    rmdir($item->getPathname());
                }
            } else {
                if ($item->isWritable()) {
                    unlink($item->getPathname());
                }
            }
        }
    }
}