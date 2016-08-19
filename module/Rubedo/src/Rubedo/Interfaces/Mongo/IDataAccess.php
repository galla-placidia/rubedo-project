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
namespace Rubedo\Interfaces\Mongo;

/**
 * Interface of data access services
 *
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
interface IDataAccess
{

    /**
     * Initialize a data service handler to read or write in a DataBase
     * Collection
     *
     * @param string $collection
     *            name of the DB
     * @param string $dbName
     *            name of the DB
     * @param string $connection
     *            connection string to the DB server
     */
    public function init($collection, $dbName = null, $connection = null);

    /**
     * Do a find request on the current collection
     *
     * @param \WebTales\MongoFilters\IFilter $filters
     * @return array
     */
    public function read(\WebTales\MongoFilters\IFilter $filters = null);

    /**
     * Do a findone request on the current collection
     *
     * @see \Rubedo\Interfaces\IDataAccess::findOne()
     * @param \WebTales\MongoFilters\IFilter $value
     *            search condition
     * @return array
     */
    public function findOne(\WebTales\MongoFilters\IFilter $value);

    /**
     * Find an item given by its literral ID
     *
     * @param string $contentId
     * @return array
     */
    public function findById($contentId);

    /**
     * Find an item given by its name (find only one if many)
     *
     * @param string $name
     * @return array
     */
    public function findByName($name);

    /**
     * Create an objet in the current collection
     *
     * @param array $obj
     *            data object
     * @param array $options
     * @return array
     */
    public function create(array $obj, $options = array());

    /**
     * Update an objet in the current collection
     *
     * @param array $obj
     *            data object
     * @param array $options
     * @return array
     */
    public function update(array $obj, $options = array());

    /**
     * Update an objet in the current collection
     *
     * @param array $obj
     *            data object
     * @param array $options
     * @return array
     */
    public function destroy(array $obj, $options = array());

    /**
     * Do a find request on the current collection and return content as tree
     *
     * @param \WebTales\MongoFilters\IFilter $filters
     * @return array
     */
    public function readTree(\WebTales\MongoFilters\IFilter $filters = null);

    /**
     * Find child of a node tree
     *
     * @param \WebTales\MongoFilters\IFilter $filters
     * @param $parentId id
     *            of the parent node
     * @return array children array
     */
    public function readChild($parentId, \WebTales\MongoFilters\IFilter $filters = null);

    /**
     * Do a count request based on current filter
     *
     * @param \WebTales\MongoFilters\IFilter $filters
     * @return integer
     */
    public function count(\WebTales\MongoFilters\IFilter $filters = null);

    /**
     * Add a filter condition to the service
     *
     * These filters are shared between all queries to the service
     *
     * Filter should be
     * array('field'=>'value')
     * or
     * array('field'=>array('operator'=>value))
     *
     * @param \WebTales\MongoFilters\IFilter $filter
     * @return bool
     */
    public function addFilter(\WebTales\MongoFilters\IFilter $filter);


    /**
     * Return the current MongoDB conditions.
     *
     * @return \WebTales\MongoFilters\IFilter
     */
    public function getFilters();

    /**
     * Unset all filter condition to the service
     *
     * @return bool
     */
    /**
     * Set the main MongoDB connection string
     *
     * @param string $mongo
     * @throws \Exception
     */
    public static function setDefaultMongo($mongo);

    /**
     * Set the main Database name
     *
     * @param string $dbName
     * @throws \Exception
     */
    public static function setDefaultDb($dbName);

    /**
     * Clear the current Filters for this instance of the service
     */
    public function clearFilter();

    /**
     * Add a sort condition to the service
     *
     * Sort should be
     * array('field'=>'value')
     * or
     * array('field'=>array('operator'=>value))
     *
     * @param array $sort
     *            Native Mongo syntax sort array
     * @return bool
     */
    public function addSort(array $sort);

    /**
     * Return the current array of conditions.
     *
     * @return array
     */
    public function getSortArray();

    /**
     * Unset all sort condition to the service
     *
     * @return bool
     */
    public function clearSort();

    /**
     * Set the number of the first result displayed
     *
     * @param $firstResult is
     *            the number of the first result displayed
     */
    public function setFirstResult($firstResult);

    /**
     * Set the number of results displayed
     *
     * @param $numberOfResults is
     *            the number of results displayed
     */
    public function setNumberOfResults($numberOfResults);

    /**
     * Set to zer the number of the first result displayed
     */
    public function clearFirstResult();

    /**
     * Set to zero (unlimited) the number of results displayed
     */
    public function clearNumberOfResults();

    /**
     * Return the current number of the first result displayed
     *
     * @return integer
     */
    public function getFirstResult();

    /**
     * Return the current number of results displayed
     *
     * @return integer
     */
    public function getNumberOfResults();

    /**
     * Add to the field list the array passed in argument
     *
     * @param array $fieldList
     */
    public function addToFieldList(array $fieldList);

    /**
     * Give the fields into the fieldList array
     *
     * @return array
     */
    public function getFieldList();

    /**
     * Allow to remove one field in the current array
     *
     * @param array $fieldToRemove
     */
    public function removeFromFieldList(array $fieldToRemove);

    /**
     * Clear the fieldList array
     */
    public function clearFieldList();

    /**
     * Add to the exclude field list the array passed in argument
     *
     * @param array $excludeFieldList
     */
    public function addToExcludeFieldList(array $excludeFieldList);

    /**
     * Give the fields into the excludeFieldList array
     */
    public function getExcludeFieldList();

    /**
     * Allow to remove one field in the current excludeFieldList array
     *
     * @param array $fieldToRemove
     */
    public function removeFromExcludeFieldList(array $fieldToRemove);

    /**
     * Clear the excludeFieldList array
     */
    public function clearExcludeFieldList();

    /**
     * Add index to collection
     *
     * @param string|array $keys
     * @param array $options
     * @return boolean
     */
    public function ensureIndex($keys, $options = array());

    /**
     * Drop all indexes
     *
     * @return boolean
     */
    public function dropIndexes();

    /**
     * Check if the index is set
     *
     * @param array $keys
     * @return boolean
     */
    public function checkIndex($keys);

    /**
     * Delete with filter
     *
     * @param \WebTales\MongoFilters\IFilter $deleteCond
     * @param array $options
     * @return mixed
     */
    public function customDelete(\WebTales\MongoFilters\IFilter $deleteCond, $options = array());

    /**
     * Find with filter
     *
     * @param \WebTales\MongoFilters\IFilter $filter
     * @param array $fieldRule
     * @return mixed
     */
    public function customFind(\WebTales\MongoFilters\IFilter $filter = null, $fieldRule = array());

    /**
     * Update an objet in the current collection
     *
     * Shouldn't be used if doing a simple update action
     *
     * @see \Rubedo\Interfaces\IDataAccess::customUpdate
     * @param array $data
     *            data to update
     * @param \WebTales\MongoFilters\IFilter $updateCond
     *            condition to determine what should be updated
     * @param array $options
     * @return array
     */
    public function customUpdate(array $data, \WebTales\MongoFilters\IFilter $updateCond, $options = array());

    /**
     * Return the mongo date
     *
     * @return \MongoDate
     */
    public function getMongoDate();

    /**
     * Cast string to MongoId
     *
     * @param string $idString
     * @return \MongoId
     */
    public function getId($idString = null);

    /**
     * Cast string to MongoRegex
     *
     * @param string $expr
     * @return \MongoRegex
     */
    public function getRegex($expr);
}
