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

namespace SimpleThings\EntityAudit\Tests;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\EventManager;
use Doctrine\ORM\EntityManager;
use SimpleThings\EntityAudit\AuditConfiguration;
use SimpleThings\EntityAudit\AuditManager;

abstract class BaseTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityManager
     */
    protected $em = null;

    /**
     * @var AuditManager
     */
    protected $auditManager = null;

    protected $schemaEntities = array();

    protected $auditedEntities = array();

    protected $cache = null;

    protected function runCached($test)
    {
        $reader = $this->auditManager->createAuditReader($this->em);

        $this->cache = new ArrayCache();

        $reader->setCache($this->cache);

        call_user_func(array($this, $test), $reader);

        $ref = new \ReflectionClass($this->cache);

        $dataProperty = $ref->getProperty('data');

        $dataProperty->setAccessible(true);

        return $dataProperty->getValue($this->cache);
    }

    protected function clearCache()
    {
        if ($this->cache) {
            $this->cache->flushAll();
        }
    }

    public function setUp()
    {
        $reader = new \Doctrine\Common\Annotations\AnnotationReader();
        $driver = new \Doctrine\ORM\Mapping\Driver\AnnotationDriver($reader);
        $config = new \Doctrine\ORM\Configuration();
        $config->setMetadataCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setQueryCacheImpl(new \Doctrine\Common\Cache\ArrayCache);
        $config->setProxyDir(sys_get_temp_dir());
        $config->setProxyNamespace('SimpleThings\EntityAudit\Tests\Proxies');
        $config->setMetadataDriverImpl($driver);

        $conn = array(
            'driver' => $GLOBALS['DOCTRINE_DRIVER'],
            'memory' => $GLOBALS['DOCTRINE_MEMORY'],
            'dbname' => $GLOBALS['DOCTRINE_DATABASE'],
            'user' => $GLOBALS['DOCTRINE_USER'],
            'password' => $GLOBALS['DOCTRINE_PASSWORD'],
            'host' => $GLOBALS['DOCTRINE_HOST']
        );

        $auditConfig = new AuditConfiguration();
        $auditConfig->setCurrentUsername("beberlei");
        $auditConfig->setAuditedEntityClasses($this->auditedEntities);
        $auditConfig->setGlobalIgnoreColumns(array('ignoreme'));

        $this->auditManager = new AuditManager($auditConfig);
        $this->auditManager->registerEvents($evm = new EventManager());

        if (php_sapi_name() == 'cli' && isset($_SERVER['argv']) && (in_array('-v', $_SERVER['argv']) || in_array('--verbose', $_SERVER['argv']))) {
            $config->setSQLLogger(new \Doctrine\DBAL\Logging\EchoSQLLogger());
        }

        $this->em = \Doctrine\ORM\EntityManager::create($conn, $config, $evm);

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $em = $this->em;

        try {
            $schemaTool->createSchema(array_map(function ($value) use ($em) {
                return $em->getClassMetadata($value);
            }, $this->schemaEntities));
        } catch (\Exception $e) {
            if ($GLOBALS['DOCTRINE_DRIVER'] != 'pdo_mysql' ||
                !($e instanceof \PDOException && strpos($e->getMessage(), 'Base table or view already exists') !== false)
            ) {
                throw $e;
            }
        }
    }

    public function tearDown()
    {
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->em);
        $em = $this->em;

        try {
            $schemaTool->dropSchema(array_map(function ($value) use ($em) {
                    return $em->getClassMetadata($value);
                }, $this->schemaEntities));
        } catch (\Exception $e) {
            if ($GLOBALS['DOCTRINE_DRIVER'] != 'pdo_mysql' ||
                !($e instanceof \PDOException && strpos($e->getMessage(), 'Base table or view already exists') !== false)
            ) {
                throw $e;
            }
        }
    }
}
