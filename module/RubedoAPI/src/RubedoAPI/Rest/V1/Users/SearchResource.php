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

namespace RubedoAPI\Rest\V1\Users;

use RubedoAPI\Entities\API\Definition\VerbDefinitionEntity;
use RubedoAPI\Rest\V1\SearchResource as GlobalSearch;

/**
 * Class SearchResource
 * @package RubedoAPI\Rest\V1\Users
 */
class SearchResource extends GlobalSearch
{
    /**
     * {@inheritdoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->searchOption = 'user';
        $this
            ->definition
            ->setName('Users')
            ->setDescription('Deal with users')
            ->editVerb('get', function (VerbDefinitionEntity &$entity) {
                $entity
                    ->setDescription('Get a list of users using Elastic Search');
            });
    }
}