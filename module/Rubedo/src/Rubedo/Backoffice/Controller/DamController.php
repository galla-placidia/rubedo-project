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
namespace Rubedo\Backoffice\Controller;

use Rubedo\Services\Manager;
use Zend\Json\Json;
use Zend\View\Model\JsonModel;
use Rubedo\Exceptions\User;

/**
 * Controller providing CRUD API for the Groups JSON
 *
 * Receveive Ajax Calls for read & write from the UI to the Mongo DB
 *
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 *
 */
class DamController extends DataAccessController
{

    /**
     * Contain the MIME type
     */
    protected $_mimeType = "";

    public function __construct()
    {
        parent::__construct();

        // init the data access service
        $this->_dataService = Manager::getService('Dam');
    }

    /*
     * (non-PHPdoc) @see DataAccessController::indexAction()
     */
    public function indexAction()
    {
        // merge filter and tFilter
        $jsonFilter = $this->params()->fromQuery('filter', '[]');
        $jsonTFilter = $this->params()->fromQuery('tFilter', '[]');
        $filterArray = Json::decode($jsonFilter, Json::TYPE_ARRAY);
        $tFilterArray = Json::decode($jsonTFilter, Json::TYPE_ARRAY);
        $globalFilterArray = array_merge($tFilterArray, $filterArray);

        // call standard method with merge array
        $this->getRequest()
            ->getQuery()
            ->set('filter', Json::encode($globalFilterArray));
        return parent::indexAction();
    }

    public function getThumbnailAction()
    {
        $mediaId = $this->params()->fromQuery('id', null);
        if (!$mediaId) {
            throw new \Rubedo\Exceptions\User('no id given', "Exception7");
        }
        $media = $this->_dataService->findById($mediaId);
        if (!$media) {
            throw new \Rubedo\Exceptions\NotFound('no media found', "Exception8");
        }
        $version = $this->params()->fromQuery('version', $media['id']);
        if ($media['mainFileType'] == 'Image') {
            $queryString = $this->getRequest()->getQuery();
            $queryString->set('file-id', $media['originalFileId']);
            $queryString->set('version', $version);
            return $this->forward()->dispatch('Rubedo\\Frontoffice\\Controller\\Image', array(
                'action' => 'get-thumbnail'
            ));
        } else {
            $queryString = $this->getRequest()->getQuery();
            $queryString->set('file-id', $media['originalFileId']);
            $queryString->set('version', $version);
            if (isset($media["mainFileType"])&&$media["mainFileType"]&&$media["mainFileType"]!=""){
                $queryString->set('file-type', $media["mainFileType"]);
                if ($media["mainFileType"]=="Document"&&isset($media["Content-Type"])&&$media["Content-Type"]&&$media["Content-Type"]!=""){
                    $queryString->set('content-type', $media["Content-Type"]);
                }
            }
            return $this->forward()->dispatch('Rubedo\\Frontoffice\\Controller\\File', array(
                'action' => 'get-thumbnail'
            ));
        }
    }

    public function createAction()
    {
        $typeId = $this->params()->fromPost('typeId');
        if (!$typeId) {
            throw new \Rubedo\Exceptions\User('no type ID Given', "Exception3");
        }
        $damType = Manager::getService('DamTypes')->findById($typeId);
        $damDirectory = $this->params()->fromPost('directory', 'notFiled');
        $nativeLanguage = $this->params()->fromPost('workingLanguage', 'en');
        if (!$damType) {
            throw new \Rubedo\Exceptions\Server('unknown type', "Exception9");
        }
        $obj['typeId'] = $damType['id'];
        $obj['directory'] = $damDirectory;
        $obj['mainFileType'] = $damType['mainFileType'];

        $title = $this->params()->fromPost('title');
        if (!$title) {
            throw new \Rubedo\Exceptions\User('missing title', "Exception10");
        }

        $obj['title'] = $title;
        $obj['fields']['title'] = $title;
        $obj['taxonomy'] = Json::decode($this->params()->fromPost('taxonomy', '[]'), Json::TYPE_ARRAY);

        $workspace = $this->params()->fromPost('writeWorkspace');
        if (!is_null($workspace) && $workspace != "") {
            $obj['writeWorkspace'] = $workspace;
            $obj['fields']['writeWorkspace'] = $workspace;
        }

        $targets = Json::decode($this->params()->fromPost('targetArray'), Json::TYPE_ARRAY);
        if (is_array($targets) && count($targets) > 0) {
            $obj['target'] = $targets;
            $obj['fields']['target'] = $targets;
        }

        $fields = $damType['fields'];

        foreach ($fields as $field) {
            if ($field['cType'] == 'Ext.form.field.File') {
                continue;
            }
            $fieldConfig = $field['config'];
            $name = $fieldConfig['name'];
            $obj['fields'][$name] = $this->params()->fromPost($name);
            if (!$fieldConfig['allowBlank'] && !$obj['fields'][$name]) {
                throw new \Rubedo\Exceptions\User('Required field missing: %1$s', 'Exception4', $name);
            }
        }

        foreach ($fields as $field) {
            if ($field['cType'] !== 'Ext.form.field.File') {
                continue;
            }
            $fieldConfig = $field['config'];
            $name = $fieldConfig['name'];

            $uploadResult = $this->_uploadFile($name, $fieldConfig['fileType']);
            if (!is_array($uploadResult)) {
                $obj['fields'][$name] = $uploadResult;
            }

            if (!$fieldConfig['allowBlank'] && !$obj['fields'][$name]) {
                throw new \Rubedo\Exceptions\User('Required field missing: %1$s', 'Exception4', $name);
            }
        }

        $uploadResult = $this->_uploadFile('originalFileId', $damType['mainFileType']);
        if (!is_array($uploadResult)) {
            $obj['originalFileId'] = $uploadResult;
        } else {
            return $this->_returnJson($uploadResult);
        }

        $obj['Content-Type'] = $this->_mimeType;

        if (!$obj['originalFileId']) {
            $this->getResponse()->setStatusCode(500);
            return $this->_returnJson(array(
                'success' => false,
                'msg' => 'no main file uploaded'
            ));
        }
        $obj['nativeLanguage'] = $nativeLanguage;
        $obj['i18n'] = array();
        $obj['i18n'][$nativeLanguage] = array();
        $obj['i18n'][$nativeLanguage]['fields'] = $obj['fields'];
        unset($obj['i18n'][$nativeLanguage]['fields']['writeWorkspace']);
        unset($obj['i18n'][$nativeLanguage]['fields']['target']);
        $returnArray = $this->_dataService->create($obj);

        if (!$returnArray['success']) {
            $this->getResponse()->setStatusCode(500);
        }
        $content = Json::encode($returnArray);
        $response = $this->getResponse();
        $headers = $response->getHeaders();
        $headers->addHeaderLine('Content-Type', 'text/html');
        $response->setContent($content);
        return $response;
    }

    /*
     * Method used by Back Office mass uploader for each file
     */
    public function massUploadAction()
    {
        $typeId = $this->params()->fromPost('typeId');
        if (!$typeId) {
            throw new \Rubedo\Exceptions\User('no type ID Given', "Exception3");
        }
        $damType = Manager::getService('DamTypes')->findById($typeId);
        $nativeLanguage = $this->params()->fromPost('workingLanguage', 'en');
        if (!$damType) {
            throw new \Rubedo\Exceptions\Server('unknown type', "Exception9");
        }
        $obj = array();
        $damDirectory = $this->params()->fromPost('directory', 'notFiled');
        $obj['directory'] = $damDirectory;
        $obj['typeId'] = $damType['id'];
        $obj['mainFileType'] = $damType['mainFileType'];
        $obj['fields'] = array();
        $obj['taxonomy'] = array();
        $encodedActiveFacets = $this->params()->fromPost('activeFacets');
        $activeFacets = Json::decode($encodedActiveFacets);
        $applyTaxoFacets = $this->params()->fromPost('applyTaxoFacets', false);
        if (($applyTaxoFacets) && ($applyTaxoFacets != "false")) {
            $obj['taxonomy'] = $activeFacets;
        }
        $workspace = $this->params()->fromPost('writeWorkspace');
        if (!is_null($workspace) && $workspace != "") {
            $obj['writeWorkspace'] = $workspace;
            $obj['fields']['writeWorkspace'] = $workspace;
        }
        $targets = Json::decode($this->params()->fromPost('targetArray'));
        if (is_array($targets) && count($targets) > 0) {
            $obj['target'] = $targets;
            $obj['fields']['target'] = $targets;
        }
        $uploadResult = $this->_uploadFile('file', $damType['mainFileType'], true, true);
        if ($uploadResult['success']) {
            $properName = explode(".", $uploadResult['data']['text']);
            $obj['title'] = $properName[0];
            $obj['fields']['title'] = $properName[0];
            $obj['originalFileId'] = $uploadResult['data']['id'];
        } else {
            $this->getResponse()->setStatusCode(500);
            return new JsonModel($uploadResult);
        }
        $obj['Content-Type'] = $this->_mimeType;
        if (!$obj['originalFileId']) {
            $this->getResponse()->setStatusCode(500);
            return $this->_returnJson(array(
                'success' => false,
                'msg' => 'no main file uploaded'
            ));
        }
        $obj['nativeLanguage'] = $nativeLanguage;
        $obj['i18n'] = array();
        $obj['i18n'][$nativeLanguage] = array();
        $obj['i18n'][$nativeLanguage]['fields'] = $obj['fields'];
        unset($obj['i18n'][$nativeLanguage]['fields']['writeWorkspace']);
        unset($obj['i18n'][$nativeLanguage]['fields']['target']);
        $returnArray = $this->_dataService->create($obj);
        if (!$returnArray['success']) {
            $this->getResponse()->setStatusCode(500);
        }
        return new JsonModel($returnArray);
    }

    protected function _uploadFile($name, $fileType, $returnFullResult = false, $setMimeType = false)
    {
        $fileInfos = $this->params()->fromFiles($name);

        if (isset($fileInfos["error"]) && $fileInfos["error"] != UPLOAD_ERR_OK) {
            switch ($fileInfos["error"]) {
                case UPLOAD_ERR_INI_SIZE:
                    $msg = "The server does not allow you to upload a file bigger than " . ini_get('upload_max_filesize');
                    break;
                case UPLOAD_ERR_FORM_SIZE:
                    $msg = "You can not upload a file bigger than the form allow you to do";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $msg = "The uploaded file was only partially uploaded";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $msg = "No file uploaded for this field : " . $name;
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $msg = "Missing a temporary folder";
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $msg = "Failed to write file to disk";
                    break;
                case UPLOAD_ERR_EXTENSION:
                    $msg = "A PHP extension stopped the file upload";
                    break;
                default :
                    $msg = "No file uploaded for this field : " . $name;
                    break;
            }

            return (array(
                "success" => false,
                "msg" => $msg
            ));
        }

        $mimeType = mime_content_type($fileInfos['tmp_name']);

        if (($name == 'originalFileId') || ($setMimeType)) {
            $this->_mimeType = $mimeType;
        }

        $fileService = Manager::getService('Files');

        $fileObj = array(
            'serverFilename' => $fileInfos['tmp_name'],
            'text' => $fileInfos['name'],
            'filename' => $fileInfos['name'],
            'Content-Type' => isset($mimeType) ? $mimeType : $fileInfos['type'],
            'mainFileType' => $fileType
        );
        $result = $fileService->create($fileObj);
        if ((!$result['success']) || ($returnFullResult)) {
            return $result;
        }
        return $result['data']['id'];
    }

    public function deleteByDamTypeIdAction()
    {
        $typeId = $this->params()->fromPost('type-id');
        if (!$typeId) {
            throw new User('This action needs a type-id as argument.', 'Exception3');
        }
        $deleteResult = $this->_dataService->deleteByDamType($typeId);

        return $this->_returnJson($deleteResult);
    }

    /**
     * Do a findOneAction
     */
    public function findOneAction()
    {
        $contentId = $this->params()->fromQuery('id');

        if (!is_null($contentId)) {

            $return = $this->_dataService->findById($contentId, false, false);

            if (empty($return['id'])) {

                $returnArray = array(
                    'success' => false,
                    "msg" => 'Object not found'
                );
            } else {

                $returnArray = array(
                    'succes' => true,
                    'data' => $return
                );
            }
        } else {

            $returnArray = array(
                'success' => false,
                "msg" => 'Missing param'
            );
        }

        return $this->_returnJson($returnArray);
    }
}
