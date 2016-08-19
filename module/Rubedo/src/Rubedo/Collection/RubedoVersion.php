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
namespace Rubedo\Collection;

use Rubedo\Interfaces\Collection\IRubedoVersion;
use Rubedo\Version\Version;
use WebTales\MongoFilters\Filter;

/**
 * Service to handle Blocks
 *
 * @author jbourdin
 * @category Rubedo
 * @package Rubedo
 */
class RubedoVersion extends AbstractCollection implements IRubedoVersion
{

    public function __construct()
    {
        $this->_collectionName = 'RubedoVersion';
        parent::__construct();
    }

    public function getDbVersion()
    {
        $versionRecord = $this->findOne(Filter::factory());
        if (!$versionRecord) {
            return '1.0.0';
        }
        return $versionRecord['rubedoVersion'];
    }

    public function setDbVersion($version)
    {
        $versionRecord = $this->findOne(Filter::factory());
        if (!$versionRecord) {
            $versionRecord = array(
                'rubedoVersion' => $version
            );
            $this->create($versionRecord);
        } else {
            $versionRecord['rubedoVersion'] = $version;
            $this->update($versionRecord);
        }
    }

    public function isDbUpToDate()
    {
        $rubedoVersion = Version::getVersion();
        $dbVersion = $this->getDbVersion();
        return (version_compare($dbVersion, $rubedoVersion) >= 0);
    }
}
