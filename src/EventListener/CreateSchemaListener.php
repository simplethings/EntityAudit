<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SimpleThings\EntityAudit\EventListener;

use Doctrine\Common\EventSubscriber;
use Doctrine\DBAL\Schema\Column;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use Doctrine\ORM\Tools\Event\GenerateSchemaTableEventArgs;
use Doctrine\ORM\Tools\ToolEvents;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;
use SimpleThings\EntityAudit\Metadata\MetadataFactory;

class CreateSchemaListener implements EventSubscriber
{
    /**
     * @var AuditConfiguration
     */
    private $config;

    /**
     * @var MetadataFactory
     */
    private $metadataFactory;

    public function __construct(AuditManager $auditManager)
    {
        $this->config = $auditManager->getConfiguration();
        $this->metadataFactory = $auditManager->getMetadataFactory();
    }

    public function getSubscribedEvents()
    {
        return [
            ToolEvents::postGenerateSchemaTable,
            ToolEvents::postGenerateSchema,
        ];
    }

    public function postGenerateSchemaTable(GenerateSchemaTableEventArgs $eventArgs): void
    {
        $cm = $eventArgs->getClassMetadata();

        if (!$this->metadataFactory->isAudited($cm->name)) {
            $audited = false;
            if ($cm->isInheritanceTypeJoined() && $cm->rootEntityName === $cm->name) {
                foreach ($cm->subClasses as $subClass) {
                    if ($this->metadataFactory->isAudited($subClass)) {
                        $audited = true;
                    }
                }
            }
            if (!$audited) {
                return;
            }
        }

        $schema = $eventArgs->getSchema();
        $entityTable = $eventArgs->getClassTable();
        $revisionTable = $schema->createTable(
            $this->config->getTablePrefix().$entityTable->getName().$this->config->getTableSuffix()
        );

        foreach ($entityTable->getColumns() as $column) {
            $columnTypeName = $column->getType()->getName();
            $columnArrayOptions = $column->toArray();

            // Ignore specific fields for subclasses in-case of using discriminator column.
            foreach ($cm->subClasses as $subClass) {
                if ($cm->hasField($column->getName()) || $cm->hasAssociation($column->getName())) {
                    if ($this->config->isEntityIgnoredProperty($subClass, $cm->getFieldForColumn($column->getName()))) {
                        continue 2;
                    }
                }
            }

            // Ignore specific fields for table.
            if (empty($cm->discriminatorColumn) && $this->config->isEntityIgnoredProperty($cm->getName(), $cm->getFieldForColumn($column->getName()))) {
                continue;
            }

            /* @var Column $column */
            $revisionTable->addColumn($column->getName(), $columnTypeName, array_merge(
                $columnArrayOptions,
                ['notnull' => false, 'autoincrement' => false]
            ));
        }
        $revisionTable->addColumn($this->config->getRevisionFieldName(), $this->config->getRevisionIdFieldType());
        $revisionTable->addColumn($this->config->getRevisionTypeFieldName(), 'string', ['length' => 4]);
        if (!\in_array($cm->inheritanceType, [ClassMetadataInfo::INHERITANCE_TYPE_NONE, ClassMetadataInfo::INHERITANCE_TYPE_JOINED, ClassMetadataInfo::INHERITANCE_TYPE_SINGLE_TABLE], true)) {
            throw new \Exception(sprintf('Inheritance type "%s" is not yet supported', $cm->inheritanceType));
        }

        $pkColumns = $entityTable->getPrimaryKey()->getColumns();
        $pkColumns[] = $this->config->getRevisionFieldName();
        $revisionTable->setPrimaryKey($pkColumns);
        $revIndexName = $this->config->getRevisionFieldName().'_'.md5($revisionTable->getName()).'_idx';
        $revisionTable->addIndex([$this->config->getRevisionFieldName()], $revIndexName);
    }

    public function postGenerateSchema(GenerateSchemaEventArgs $eventArgs): void
    {
        $schema = $eventArgs->getSchema();
        $revisionsTable = $schema->createTable($this->config->getRevisionTableName());
        $revisionsTable->addColumn('id', $this->config->getRevisionIdFieldType(), [
            'autoincrement' => true,
        ]);
        $revisionsTable->addColumn('timestamp', 'datetime');
        $revisionsTable->addColumn('username', 'string')->setNotnull(false);
        $revisionsTable->setPrimaryKey(['id']);
    }
}
