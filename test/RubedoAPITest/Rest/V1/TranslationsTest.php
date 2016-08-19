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

namespace RubedoAPITest\Rest\V1;

use Rubedo\Services\Manager;
use RubedoAPI\Entities\API\Language;
use RubedoAPI\Rest\V1\TranslationsResource;

class TranslationsTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \RubedoAPI\Rest\V1\TranslationsResource
     */
    protected $resource;
    protected $translations;

    function setUp()
    {
        $this->resource = new TranslationsResource();
        $this->translations = $this->getMock('RubedoAPI\Services\Internationalization\Translate');
        Manager::setMockService('API\\Services\\Translation', $this->translations);
        parent::setUp();
    }

    function tearDown()
    {
        Manager::resetMocks();
        parent::tearDown();
    }

    public function testDefinition()
    {
        $this->assertNotNull($this->resource->getDefinition()->getVerb('get'));
    }

    public function testGet()
    {
        $result = $this->resource->getAction(array(
            'lang' => new Language('fr')
        ));
        $this->assertArrayHasKey('success', $result);
        $this->assertArrayHasKey('translations', $result);

    }
}