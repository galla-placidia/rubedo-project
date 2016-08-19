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
namespace RubedoTest\Collection;

use Rubedo\Collection\UrlCache;
use Rubedo\Services\Manager;

/**
 * Test suite of the collection service :
 * @author jbourdin
 * @category Rubedo-Test
 * @package Rubedo-Test
 */
class UrlCacheTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Rubedo\Mongo\DataAccess
     */
    private $mockDataAccessService;

    /**
     * clear the DB of the previous test data
     */
    public function tearDown()
    {
        Manager::resetMocks();
    }

    /**
     * init the Zend Application for tests
     */
    public function setUp()
    {
        $this->mockDataAccessService = $this->getMock('Rubedo\Mongo\DataAccess');
        Manager::setMockService('MongoDataAccess', $this->mockDataAccessService);


        parent::setUp();
    }

    /*
     * test if findByPageId function start findOne funtion once.
     */
    public function testNormalfindByPageId()
    {
        $this->mockDataAccessService->expects($this->once())->method('findOne');

        $pageId = "testId";
        $urlCacheService = new UrlCache();
        $urlCacheService->findByPageId($pageId, 'en');
    }

    /*
 * test if findByUrl function start findOne funtion once.
 */
    public function testNormalfindByUrl()
    {
        $this->mockDataAccessService->expects($this->once())->method('findOne');

        $url = "testId";
        $siteId = "testSiteId";
        $urlCacheService = new UrlCache();
        $urlCacheService->findByUrl($url, $siteId);
    }

    /*
     * test if create fuction works fine.
     */
    public function testNormalCreate()
    {
        $this->mockDataAccessService->expects($this->once())->method('getMongoDate');
        $this->mockDataAccessService->expects($this->once())->method('create');

        $obj["test"] = "test";
        $urlCacheService = new UrlCache();
        $urlCacheService->create($obj);
    }

    /*
     * test if verifyIndexes function start ensureIndex twice.
     */
    public function testVerifyIndexes()
    {
        $this->mockDataAccessService->expects($this->exactly(2))->method('ensureIndex');

        $obj["test"] = "test";
        $urlCacheService = new UrlCache();
        $urlCacheService->verifyIndexes();

    }


}
