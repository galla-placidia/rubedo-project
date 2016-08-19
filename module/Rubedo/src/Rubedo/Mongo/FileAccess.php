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
namespace Rubedo\Mongo;

use Rubedo\Interfaces\Mongo\IFileAccess;

/**
 * Class implementing the API to MongoDB
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
class FileAccess extends DataAccess implements IFileAccess
{

    /**
     * Object which represent the mongoDB Collection
     *
     * @var \MongoGridFS
     */
    protected $_collection;

    /**
     * Initialize a data service handler to read or write in a MongoDb
     * Collection
     *
     * @param string $collection
     *            name of the DB
     * @param string $dbName
     *            name of the DB
     * @param string $mongo
     *            connection string to the DB server
     */
    public function init($collection = null, $dbName = null, $mongo = null)
    {
        unset($collection);

        $mongo = self::$_defaultMongo;
        $dbName = self::$_defaultDb;

		$this->_adapter = $this->getAdapter($mongo,$dbName);
		
        $this->_dbName = $this->_adapter->$dbName;
        $this->_collection = new ProxyGridFs($this->_dbName->getGridFS());
    }

    /**
     * Do a find request on the current collection
     *
     * @see \Rubedo\Interfaces\IDataAccess::read()
     * @return array
     */
    public function read(\WebTales\MongoFilters\IFilter $filters = null)
    {
        // get the UI parameters
        $LocalFilter = clone $this->getFilters();

        // add Read Filters
        if ($filters) {
            $LocalFilter->addFilter($filters);
        }

        $sort = $this->getSortArray();
        $firstResult = $this->getFirstResult();
        $numberOfResults = $this->getNumberOfResults();
        $includedFields = $this->getFieldList();
        $excludedFields = $this->getExcludeFieldList();

        // merge the two fields array to obtain only one array with all the
        // conditions
        if (!empty($includedFields) && !empty($excludedFields)) {
            $fieldRule = $includedFields;
        } else {
            $fieldRule = array_merge($includedFields, $excludedFields);
        }

        // get the cursor
        $cursor = $this->_collection->find($LocalFilter->toArray(), $fieldRule);
        $nbItems = $cursor->count();

        // apply sort, paging, filter
        $cursor->sort($sort);
        $cursor->skip($firstResult);
        $cursor->limit($numberOfResults);

        $data = array();
        // switch from cursor to actual array
        foreach ($cursor as $value) {
            $data[] = $value;
        }

        // return data as simple array with no keys
        $datas = array_values($data);
        $returnArray = array(
            "data" => $datas,
            'count' => $nbItems
        );
        return $returnArray;
    }

    /**
     * Do a findone request on the current collection
     *
     * @see \Rubedo\Interfaces\IDataAccess::findOne()
     * @param array $value
     *            search condition
     * @return array
     */
    public function findOne(\WebTales\MongoFilters\IFilter $localFilter = null)
    {
        // get the UI parameters
        $includedFields = $this->getFieldList();
        $excludedFields = $this->getExcludeFieldList();

        // merge the two fields array to obtain only one array with all the
        // conditions
        if (!empty($includedFields) && !empty($excludedFields)) {
            $fieldRule = $includedFields;
        } else {
            $fieldRule = array_merge($includedFields, $excludedFields);
        }

        $filters = clone $this->getFilters();
        if ($localFilter) {
            $filters->addFilter($localFilter);
        }

        $mongoFile = $this->_collection->findOne($filters->toArray(), $fieldRule);

        return $mongoFile;
    }

    /**
     * Create an objet in the current collection
     *
     * @see \Rubedo\Interfaces\IDataAccess::create
     * @param array $obj
     *            data object
     * @param bool $options
     *            should we wait for a server response
     * @return array
     */
    public function create(array $obj, $options = array())
    {
        $filename = $obj['serverFilename'];
        $partList = explode('.', $filename);
        $extension = array_pop($partList);

        if (!$this->_isValidExtension($extension)) {
            return array(
                'success' => false,
                'msg' => 'not allowed file extension : ' . $extension
            );
        }

        $mimeType = $obj['Content-Type'];
        if (!$this->_isValidContentType($mimeType)) {
            return array(
                'success' => false,
                'msg' => 'not allowed file type : ' . $mimeType
            );
        }

        $obj['version'] = 1;

        unset($obj['serverFilename']);

        $currentUserService = \Rubedo\Services\Manager::getService('CurrentUser');
        $currentUser = $currentUserService->getCurrentUserSummary();
        $obj['lastUpdateUser'] = $currentUser;
        $obj['createUser'] = $currentUser;

        $currentTimeService = \Rubedo\Services\Manager::getService('CurrentTime');
        $currentTime = $currentTimeService->getCurrentTime();

        $obj['createTime'] = $currentTime;
        $obj['lastUpdateTime'] = $currentTime;

        $fileId = $this->_collection->put($filename, $obj);

        if ($fileId) {
            $obj['id'] = (string)$fileId;
            $returnArray = array(
                'success' => true,
                "data" => $obj
            );
        } else {
            $returnArray = array(
                'success' => false
            );
        }

        return $returnArray;
    }

    public function createBinary(array $obj, $options = array())
    {
        unset($options); //unused for the moment
        $bites = $obj['bytes'];

        $filename = $obj['filename'];
        $partList = explode('.', $filename);
        $extension = array_pop($partList);

        if (!$this->_isValidExtension($extension)) {
            return array(
                'success' => false,
                'msg' => 'not allowed file extension : ' . $extension
            );
        }

        $mimeType = $obj['Content-Type'];
        if (!$this->_isValidContentType($mimeType)) {
            return array(
                'success' => false,
                'msg' => 'not allowed file type : ' . $mimeType
            );
        }

        $obj['version'] = 1;

        unset($obj['bytes']);

        $currentUserService = \Rubedo\Services\Manager::getService('CurrentUser');
        $currentUser = $currentUserService->getCurrentUserSummary();
        $obj['lastUpdateUser'] = $currentUser;
        $obj['createUser'] = $currentUser;

        $currentTimeService = \Rubedo\Services\Manager::getService('CurrentTime');
        $currentTime = $currentTimeService->getCurrentTime();

        $obj['createTime'] = $currentTime;
        $obj['lastUpdateTime'] = $currentTime;

        $fileId = $this->_collection->storeBytes($bites, $obj);

        if ($fileId) {
            $obj['id'] = (string)$fileId;
            $returnArray = array(
                'success' => true,
                "data" => $obj
            );
        } else {
            $returnArray = array(
                'success' => false
            );
        }

        return $returnArray;
    }

    /**
     * Delete objets in the current collection
     *
     * @see \Rubedo\Interfaces\IDataAccess::destroy
     * @param array $obj
     *            data object
     * @return array
     */
    public function destroy(array $obj, $options = array())
    {
        $id = $obj['id'];
        $mongoID = $this->getId($id);

        $updateCondition = array(
            '_id' => $mongoID
        );

        if (is_array($this->_filters)) {
            $updateCondition = array_merge($this->_filters, $updateCondition);
        }

        $resultArray = $this->_collection->remove($updateCondition, $options);
        if ($resultArray['ok'] == 1) {
            if ($resultArray['n'] == 1) {
                $returnArray = array(
                    'success' => true
                );
            } else {
                $returnArray = array(
                    'success' => false,
                    "msg" => 'Impossible de supprimer le fichier'
                );
            }
        } else {
            $returnArray = array(
                'success' => false,
                "msg" => $resultArray["err"]
            );
        }

        return $returnArray;
    }

    public function drop()
    {
        return $this->_collection->drop();
    }

    protected function _isValidContentType($contentType)
    {
        list ($type) = explode(';', $contentType);
        list ($subtype, $applicationType) = explode('/', $type);
        unset($subtype);
        if ($applicationType == 'x-php') {
            return false;
        }
        return true;
    }

    protected function _isValidExtension($extension)
    {
        $notAllowed = array(
            'php',
            'php3',
            'exe',
            'dll',
            'app',
            'bat',
            'sh'
        );
        if (in_array($extension, $notAllowed)) {
            return false;
        }
        return true;
    }
}
