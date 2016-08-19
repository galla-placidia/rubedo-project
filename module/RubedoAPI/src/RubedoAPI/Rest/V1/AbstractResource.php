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

use RubedoAPI\Entities\API\Definition\DefinitionEntity;
use RubedoAPI\Exceptions\APIRequestException;
use RubedoAPI\Interfaces\IResource;
use RubedoAPI\Traits\LazyServiceManager;

/**
 * Class AbstractResource
 * @package RubedoAPI\Rest\V1
 */
abstract class AbstractResource implements IResource
{
    use LazyServiceManager;

    /**
     * Context from entry point
     * @var int
     */
    protected $context;

    /**
     * Cache lifetime for api cache (only for get and getEntity)
     * @var \RubedoAPI\Frontoffice\Controller\ApiController
     */
    public $cacheLifeTime=false;

    /**
     * @var \RubedoAPI\Entities\API\Definition\DefinitionEntity
     */
    protected $definition;

    /**
     * @var \RubedoAPI\Entities\API\Definition\DefinitionEntity
     */
    protected $entityDefinition;

    /**
     * Define the behavior of the resource
     */
    function __construct()
    {
        $this->definition = new DefinitionEntity();
        $this->entityDefinition = new DefinitionEntity();
    }

    /**
     * Options request
     *
     * @return array
     */
    public function optionsAction()
    {
        return $this->definition->jsonSerialize();
    }

    /**
     * Options request to entity
     *
     * @return array
     */
    public function optionsEntityAction()
    {
        return $this->entityDefinition->jsonSerialize();
    }

    /**
     * Return the definition for resource
     *
     * @return DefinitionEntity
     * @throws \RubedoAPI\Exceptions\APIRequestException
     */
    public function getDefinition()
    {
        if (!isset($this->definition))
            throw new APIRequestException('Definition is empty', 405);
        return $this->definition;
    }

    /**
     * Return the definition for entity resource
     *
     * @return DefinitionEntity
     * @throws \RubedoAPI\Exceptions\APIRequestException
     */
    public function getEntityDefinition()
    {
        if (!isset($this->entityDefinition))
            throw new APIRequestException('Entity definition is empty', 405);
        return $this->entityDefinition;
    }

    /**
     * Handle the correct action
     *
     * @todo refactor with handleEntity
     * @param $method
     * @param $params
     * @return array
     * @throws \RubedoAPI\Exceptions\APIRequestException
     */
    public function handler($method, $params)
    {
        if (!method_exists($this, $method . 'Action'))
            throw new APIRequestException('Verb not implemented', 405);
        if ($method == 'options')
            return $this->optionsAction();
        $verbDefinition = $this->getDefinition()->getVerb($method);
        $params = $verbDefinition->filterInput($params);
        $verbDefinition->checkRights();
        $this->getCurrentLocalizationAPIService()->injectLocalization($params);

        return $verbDefinition->filterOutput(
            $this->{$method . 'Action'}(
                $params
            )
        );
    }

    /**
     * Handle the correct action for entity
     *
     * @todo refactor with handle
     * @param $id
     * @param $method
     * @param $params
     * @return array
     * @throws \RubedoAPI\Exceptions\APIRequestException
     */
    public function handlerEntity($id, $method, $params)
    {
        if (!method_exists($this, $method . 'EntityAction'))
            throw new APIRequestException('Verb not implemented for an entity', 405);
        if ($method == 'options')
            return $this->optionsEntityAction();
        $verbDefinition = $this->getEntityDefinition()->getVerb($method);
        $params = $verbDefinition->filterInput($params);
        $verbDefinition->checkRights();
        $this->getCurrentLocalizationAPIService()->injectLocalization($params);

        return $verbDefinition->filterOutput(
            $this->{$method . 'EntityAction'}(
                $id,
                $params
            )
        );
    }

    /**
     * return the controller context
     *
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * set the controller context
     *
     * @param mixed $context
     * @return $this
     */
    public function setContext($context)
    {
        $this->context = $context;
        return $this;
    }
}