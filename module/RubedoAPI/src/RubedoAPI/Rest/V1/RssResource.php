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

namespace RubedoAPI\Rest\V1;


use Rubedo\Services\Manager;
use RubedoAPI\Entities\API\Definition\VerbDefinitionEntity;
use RubedoAPI\Exceptions\APIEntityException;
use Rubedo\Collection\AbstractLocalizableCollection;
use Zend\Debug\Debug;

/**
 * Class RssResource
 * @package RubedoAPI\Rest\V1
 */
class RssResource extends AbstractResource
{
    /**
     * Cache lifetime for api cache (only for get and getEntity)
     * @var int
     */
    public $cacheLifeTime=60;
    /**
     * { @inheritdoc }
     */
    public function __construct()
    {
        parent::__construct();
        $this->define();
    }


    /**
     * Get a media type
     *
     * @param $id
     * @return array
     */
    public function getEntityAction($id)
    {
        $rssFeedConfig=Manager::getService("RSSFeeds")->findById($id);
        if(!$rssFeedConfig||!$rssFeedConfig["isActivated"]){
            throw new APIEntityException('Feed not found', 404);
        }
        AbstractLocalizableCollection::setLocalizationStrategy('onlyOne');
        AbstractLocalizableCollection::setWorkingLocale($rssFeedConfig["feedLang"]);
        AbstractLocalizableCollection::setIncludeI18n(false);
        $site=Manager::getService("Sites")->findById($rssFeedConfig["siteId"]);
        $resourceObject = new ContentsResource();
        $apiResponse=$resourceObject->handler("get", $rssFeedConfig);
        $feed = new \Zend\Feed\Writer\Feed;
        $feed->setTitle($rssFeedConfig["title"]);
        $feed->setDescription($rssFeedConfig["description"]);
        $feed->setLink("http://".$site["text"]);
        $feed->setFeedLink("http://".$site["text"]."/api/v1/rss/".$rssFeedConfig["id"],"rss");
        foreach($apiResponse["contents"] as $content){
            $entry = $feed->createEntry();
            $entry->setTitle($content["fields"]["text"]);
            if(isset($content["fields"]["summary"])&&$content["fields"]["summary"]!=""){
                $entry->setDescription($content["fields"]["summary"]);
            }
            $contentUrl=$this->getUrlAPIService()->displayUrlApi($content, 'canonical', $site,$site["homePage"], $rssFeedConfig["feedLang"], null);
            $entry->setLink("http://".$site["text"].$contentUrl);
            $entry->setDateModified($content["createTime"]);
            $entry->setDateCreated($content["lastUpdateTime"]);
            $feed->addEntry($entry);
        }
        $out = $feed->export('rss');
        echo($out);
        exit();
    }


    /**
     * Define the resource
     */
    protected function define()
    {
        $this
            ->entityDefinition
            ->setName('Rss')
            ->setDescription('Get RSS feed')
            ->editVerb('get', function (VerbDefinitionEntity &$definition) {
                $this->defineGetEntity($definition);
            });
    }

    /**
     * Define get entity action
     *
     * @param VerbDefinitionEntity $definition
     */
    private function defineGetEntity(VerbDefinitionEntity &$definition)
    {
        $definition
            ->setDescription('Get RSS feed');
    }
}