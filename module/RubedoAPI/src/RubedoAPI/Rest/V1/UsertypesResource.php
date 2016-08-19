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


use RubedoAPI\Entities\API\Definition\FilterDefinitionEntity;
use RubedoAPI\Entities\API\Definition\VerbDefinitionEntity;

/**
 * Class UsertypesResource
 * @package RubedoAPI\Rest\V1
 */
class UsertypesResource extends AbstractResource
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
     * Get a list of user types
     * @return array
     */
    public function getAction()
    {
        $userTypes = $this->getUserTypesCollection()->getList()['data'];
        foreach ($userTypes as &$userType) {
            $userType = $this->filterUserType($userType);
        }
        return array(
            'success' => true,
            'userTypes' => $userTypes,
        );
    }

    /**
     * Get a user type
     *
     * @param $id
     * @return array
     */
    public function getEntityAction($id, $params)
    {
        $userType = $this->getUserTypesCollection()->findById($id, true, false);
        if (isset($userType['fields'])) {
            foreach ($userType['fields'] as &$field) {
                if (isset($field['config']['i18n'][$params['lang']->getLocale()]['fieldLabel'])) {
                    $field['config']['fieldLabel'] = $field['config']['i18n'][$params['lang']->getLocale()]['fieldLabel'];
                }
            }
        }
        return array(
            'success' => true,
            'userType' => $userType,
        );
    }

    /**
     * Filter a user type
     *
     * @param array $contentType
     * @return array
     */
    protected function filterUserType(array $contentType)
    {
        return array_intersect_key($contentType, array_flip(array('id', 'UTType', 'type',)));
    }

    /**
     * Define the resource
     */
    protected function define()
    {
        $this
            ->definition
            ->setName('User types')
            ->setDescription('Deal with user types')
            ->editVerb('get', function (VerbDefinitionEntity &$definition) {
                $this->defineGet($definition);
            });
        $this
            ->entityDefinition
            ->setName('User type')
            ->setDescription('Deal with user type')
            ->editVerb('get', function (VerbDefinitionEntity &$definition) {
                $this->defineGetEntity($definition);
            });
    }

    /**
     * Define get action
     *
     * @param VerbDefinitionEntity $definition
     */
    protected function defineGet(VerbDefinitionEntity &$definition)
    {
        $definition
            ->setDescription('Get a list of user types')
            ->addOutputFilter(
                (new FilterDefinitionEntity())
                    ->setKey('userTypes')
                    ->setDescription('The list of user types')
            );
    }

    /**
     * Define get entity action
     *
     * @param VerbDefinitionEntity $definition
     */
    private function defineGetEntity($definition)
    {
        $definition
            ->setDescription('Get a user type')
            ->addOutputFilter(
                (new FilterDefinitionEntity())
                    ->setKey('userType')
                    ->setDescription('The user type')
            );
    }
}