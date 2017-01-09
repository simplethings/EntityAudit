<?php
/*
 * (c) 2011 SimpleThings GmbH
 *
 * @package SimpleThings\EntityAudit
 * @author Benjamin Eberlei <eberlei@simplethings.de>
 * @link http://www.simplethings.de
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 */

namespace SimpleThings\EntityAudit;

use Doctrine\ORM\Mapping\ClassMetadataInfo;
use SimpleThings\EntityAudit\Exception\ConfigurationException;

class AuditConfiguration
{
    private $auditedEntityClasses = array();
    private $globalIgnoreProperties = array();
    private $tablePrefix = '';
    private $tableSuffix = '_audit';
    private $revisionTableName = 'revisions';
    private $revisionFieldName = 'rev';
    private $revisionTypeFieldName = 'revtype';
    private $revisionIdFieldType = 'integer';
    private $usernameCallable;
    private $retentionPeriodMonths = null;  // null = forever

    /**
     * @param array $classes
     *
     * @return AuditConfiguration
     */
    public static function forEntities(array $classes)
    {
        $conf = new self;
        $conf->auditedEntityClasses = $classes;

        return $conf;
    }

    /**
     * @param ClassMetadataInfo $metadata
     *
     * @return string
     */
    public function getTableName(ClassMetadataInfo $metadata)
    {
        $tableName = $metadata->getTableName();

        if ($metadata->getSchemaName()) {
            $tableName = $metadata->getSchemaName() . '.' . $tableName;
        }

        return $this->getTablePrefix() . $tableName . $this->getTableSuffix();
    }

    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;
    }

    public function getTableSuffix()
    {
        return $this->tableSuffix;
    }

    public function setTableSuffix($suffix)
    {
        $this->tableSuffix = $suffix;
    }

    public function getRevisionFieldName()
    {
        return $this->revisionFieldName;
    }

    public function setRevisionFieldName($revisionFieldName)
    {
        $this->revisionFieldName = $revisionFieldName;
    }

    public function getRevisionTypeFieldName()
    {
        return $this->revisionTypeFieldName;
    }

    public function setRevisionTypeFieldName($revisionTypeFieldName)
    {
        $this->revisionTypeFieldName = $revisionTypeFieldName;
    }

    public function getRevisionTableName()
    {
        return $this->revisionTableName;
    }

    public function setRevisionTableName($revisionTableName)
    {
        $this->revisionTableName = $revisionTableName;
    }

    public function setAuditedEntityClasses(array $classes)
    {
        $this->auditedEntityClasses = $classes;
    }

    public function getGlobalIgnoreProperties()
    {
        return $this->globalIgnoreProperties;
    }

    public function setGlobalIgnoreProperties(array $properties)
    {
        $this->globalIgnoreProperties = $properties;
    }

    public function createMetadataFactory()
    {
        return new Metadata\MetadataFactory($this->auditedEntityClasses);
    }

    /**
     * @return string|null
     */
    public function getCurrentUsername()
    {
        $callable = $this->usernameCallable;

        return $callable ? $callable() : null;
    }

    public function setUsernameCallable(callable $usernameCallable = null)
    {
        $this->usernameCallable = $usernameCallable;
    }

    /**
     * @return callable|null
     */
    public function getUsernameCallable()
    {
        return $this->usernameCallable;
    }

    public function setRevisionIdFieldType($revisionIdFieldType)
    {
        $this->revisionIdFieldType = $revisionIdFieldType;
    }

    public function getRevisionIdFieldType()
    {
        return $this->revisionIdFieldType;
    }

    /**
     * @return int|null
     */
    public function getRetentionPeriodMonths()
    {
        return $this->retentionPeriodMonths;
    }

    /**
     * @param int $retentionPeriodMonths
     * @throws ConfigurationException
     */
    public function setRetentionPeriodMonths($retentionPeriodMonths)
    {
        $this->retentionPeriodMonths = AuditPurger::getValidPeriodValue($retentionPeriodMonths);
    }
}
