<?php

namespace Symfony\Bundle\AclBundle\Tests\EventListener;

use Doctrine\DBAL\Schema\Schema as DbalSchema;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Event\GenerateSchemaEventArgs;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\AclBundle\EventListener\AclSchemaListener;
use Symfony\Component\Security\Acl\Dbal\Schema;

class AclSchemaListenerTest extends TestCase
{
    public function testAclTablesAreMergedIntoTheGeneratedSchema(): void
    {
        $target = new DbalSchema();
        $this->assertFalse($target->hasTable('acl_classes'));

        (new AclSchemaListener(new Schema([
            'class_table_name' => 'acl_classes',
            'entry_table_name' => 'acl_entries',
            'oid_table_name' => 'acl_object_identities',
            'oid_ancestors_table_name' => 'acl_object_identity_ancestors',
            'sid_table_name' => 'acl_security_identities',
        ])))->postGenerateSchema(new GenerateSchemaEventArgs($this->createStub(EntityManagerInterface::class), $target));

        foreach (['acl_classes', 'acl_entries', 'acl_object_identities', 'acl_object_identity_ancestors', 'acl_security_identities'] as $table) {
            $this->assertTrue($target->hasTable($table), $table);
        }
    }
}
