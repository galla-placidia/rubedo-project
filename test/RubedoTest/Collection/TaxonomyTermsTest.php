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

use Rubedo\Collection\TaxonomyTerms;
use Rubedo\Services\Manager;

/**
 * Test suite of the collection service :
 * @author jbourdin
 * @category Rubedo-Test
 * @package Rubedo-Test
 */
class TaxonomyTermsTest extends \PHPUnit_Framework_TestCase
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
     * test if Destroy function works fine
     */
    public function testDestroy()
    {
        $customReturn["ok"] = 1;
        $customReturn['n'] = 0;

        $this->mockDataAccessService->expects($this->once())->method('customDelete')
            ->will($this->returnValue($customReturn));

        $this->mockDataAccessService->expects($this->once())->method('readChild')
            ->will($this->returnValue(array()));

        $obj["id"] = "id";
        $obj['vocabularyId'] = "test";
        $taxonomyTermsService = new TaxonomyTerms();
        $result = $taxonomyTermsService->destroy($obj);
        $isArray = is_array($result);
        $this->assertTrue($isArray);
    }

    /*
 * test if Destroy function works fine  when customDelete function return "n">0
 */
    public function testDestroyWhenGreaterThanZero()
    {
        $customReturn["ok"] = 1;
        $customReturn['n'] = 5;

        $this->mockDataAccessService->expects($this->once())->method('customDelete')
            ->will($this->returnValue($customReturn));

        $this->mockDataAccessService->expects($this->once())->method('readChild')
            ->will($this->returnValue(array()));

        $obj["id"] = "id";
        $obj['vocabularyId'] = "test";
        $taxonomyTermsService = new TaxonomyTerms();
        $result = $taxonomyTermsService->destroy($obj);
        $isArray = is_array($result);
        $this->assertTrue($isArray);
    }

    /*
* test if Destroy function works fine  when customDelete function fail
*/
    public function testDestroyWhencustomDeleteFail()
    {
        $customReturn["ok"] = 0;
        $customReturn['n'] = 0;
        $customReturn["err"] = "error test";

        $this->mockDataAccessService->expects($this->once())->method('customDelete')
            ->will($this->returnValue($customReturn));

        $this->mockDataAccessService->expects($this->once())->method('readChild')
            ->will($this->returnValue(array()));

        $obj["id"] = "id";
        $obj['vocabularyId'] = "test";
        $taxonomyTermsService = new TaxonomyTerms();
        $result = $taxonomyTermsService->destroy($obj);
        $isArray = is_array($result);
        $this->assertTrue($isArray);
    }

    /*
     * test if function getTerm works fine
     */
    public function testGetTerm()
    {
        $mockDataVocabularyService = $this->getMock('Rubedo\\Collection\\Taxonomy');
        Manager::setMockService('Taxonomy', $mockDataVocabularyService);
        $vocabulary['id'] = 'fake';
        $vacabulary['name'] = 'fake';
        $mockDataVocabularyService->expects($this->once())->method('findById')
            ->will($this->returnValue($vacabulary));


        $findReturn["text"] = "termTest";
        $findReturn["vocabularyId"] = "fakeId";
        $this->mockDataAccessService->expects($this->once())->method('findById')
            ->will($this->returnValue($findReturn));

        $id = "id";
        $taxonomyTermsService = new TaxonomyTerms();
        $result = $taxonomyTermsService->getTerm($id);
        $this->assertEquals(array('fake' => 'termTest'), $result);

    }

    public function testFindByVocabularyId()
    {
        $this->mockDataAccessService->expects($this->once())->method('read');
        $this->mockDataAccessService->expects($this->any())->method('addFilter');

        $id = "vocabularyId";
        $taxonomyTermsService = new TaxonomyTerms();
        $result = $taxonomyTermsService->findByVocabulary($id);
    }

    public function testDeleteByVocabulary()
    {
        $this->mockDataAccessService->expects($this->once())->method('customDelete');
        $id = "vocabularyId";
        $taxonomyTermsService = new TaxonomyTerms();
        $result = $taxonomyTermsService->deleteByVocabularyId($id);
    }

}

	