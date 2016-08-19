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
namespace Rubedo\Interfaces\Elastic;

/**
 * Interface of data search indexing services
 *
 *
 * @author dfanchon
 * @category Rubedo
 * @package Rubedo
 */
interface IDataIndex
{

    /**
     * Initialize a search service handler to index data
     *
     * @param string $host
     *            http host name
     * @param string $port
     *            http port
     */
    public function init($host = null, $port = null);

    /**
     * Index ES type for new or updated content type
     *
     * @param string $id
     *            content type id
     * @param array $data
     *            new content type
     * @return array
     */
    public function indexContentType($id, $data);

    /**
     * Index ES type for new or updated dam type
     *
     * @param string $id
     *            dam type id
     * @param array $data
     *            new dam type
     * @return array
     */
    public function indexDamType($id, $data);

    /**
     * Index ES type for new or updated user type
     *
     * @param string $id
     *            user type id
     * @param array $data
     *            new user data
     * @return array
     */
    public function indexUserType($id, $data);

    /**
     * Delete ES type for content type
     *
     * @param string $id
     *            content type id
     * @return array
     */
    public function deleteContentType($id);

    /**
     * Delete existing content from index
     *
     * @param string $typeId
     *            content type id
     * @param string $id
     *            content id
     * @return array
     */
    public function deleteContent($typeId, $id);

    /**
     * Delete ES type for dam type
     *
     * @param string $id
     *            dam type id
     * @return array
     */
    public function deleteDamType($id);

    /**
     * Delete existing dam from index
     *
     * @param string $typeId
     *            content type id
     * @param string $id
     *            content id
     * @return array
     */
    public function deleteDam($typeId, $id);

    /**
     * Create or update index for existing content
     *
     * @param object $data
     *            content data
     * @return array
     */
    public function indexContent($data);

    /**
     * Create or update index for existing Dam document
     *
     * @param object $data
     *            dam data
     * @return array
     */
    public function indexDam($data);

    /**
     * Reindex all content or dam
     *
     * @param string $option
     *            : dam, content or all
     *
     * @return array
     */
    public function indexAll($option);

    /**
     * Reindex all content or dam for one type
     *
     * @param string $option
     *            : dam or content
     * @param string $id
     *            : dam type or content type id
     *
     * @return array
     */
    public function indexByType($option, $id);
}