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

/**
 * Controller providing CRUD API for the icons JSON
 *
 * Receveive Ajax Calls for read & write from the UI to the Mongo DB
 *
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 *
 */
class GenericCleaningController extends DataAccessController
{

    public function clearOrphansAction()
    {
        $iconsService = Manager::getService('Icons');
        $personalPrefsService = Manager::getService('PersonalPrefs');
        $taxonomyTermsService = Manager::getService('TaxonomyTerms');
        $pagesService = Manager::getService('Pages');
        $contentsService = Manager::getService('Contents');
        $groupsService = Manager::getService('Groups');

        $results = array();

        $results['icons'] = $iconsService->clearOrphanIcons();
        $results['personal prefs'] = $personalPrefsService->clearOrphanPrefs();
        $results['taxonomy terms'] = $taxonomyTermsService->clearOrphanTerms();
        $results['pages'] = $pagesService->clearOrphanPages();
        $results['contents'] = $contentsService->clearOrphanContents();
        $results['groups'] = $groupsService->clearOrphanGroups();

        return $this->_returnJson($results);
    }

    public function countOrphansAction()
    {
        $iconsService = Manager::getService('Icons');
        $personalPrefsService = Manager::getService('PersonalPrefs');
        $taxonomyTermsService = Manager::getService('TaxonomyTerms');
        $pagesService = Manager::getService('Pages');
        $contentsService = Manager::getService('Contents');
        $groupsService = Manager::getService('Groups');

        $results = array();

        $results['icons'] = $iconsService->countOrphanIcons();
        $results['personal prefs'] = $personalPrefsService->countOrphanPrefs();
        $results['taxonomy terms'] = $taxonomyTermsService->countOrphanTerms();
        $results['pages'] = $pagesService->countOrphanPages();
        $results['contents'] = $contentsService->countOrphanContents();
        $results['groups'] = $groupsService->countOrphanGroups();

        return $this->_returnJson($results);
    }
}