<?php

namespace Symfony\Bundle\AclBundle\Tests\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Schema as DbalSchema;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\AclBundle\Command\InitAclCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Security\Acl\Dbal\Schema;

class InitAclCommandTest extends TestCase
{
    private function aclSchema(): Schema
    {
        return new Schema([
            'class_table_name' => 'acl_classes',
            'entry_table_name' => 'acl_entries',
            'oid_table_name' => 'acl_object_identities',
            'oid_ancestors_table_name' => 'acl_object_identity_ancestors',
            'sid_table_name' => 'acl_security_identities',
        ]);
    }

    private function connection(DbalSchema $existing): Connection
    {
        $schemaManager = $this->createStub(AbstractSchemaManager::class);
        $schemaManager->method('introspectSchema')->willReturn($existing);

        $connection = $this->createMock(Connection::class);
        $connection->method('createSchemaManager')->willReturn($schemaManager);
        $connection->method('getDatabasePlatform')->willReturn(new SQLitePlatform());

        return $connection;
    }

    public function testCreatesTables(): void
    {
        $executed = [];
        $connection = $this->connection(new DbalSchema());
        $connection->expects($this->atLeastOnce())->method('executeStatement')->willReturnCallback(static function (string $sql) use (&$executed): int {
            $executed[] = $sql;

            return 0;
        });

        $tester = new CommandTester(new InitAclCommand($connection, $this->aclSchema()));

        $this->assertSame(0, $tester->execute([]));
        $this->assertStringContainsString('ACL tables have been initialized successfully.', $tester->getDisplay());
        $this->assertNotEmpty($executed);
        $this->assertStringContainsString('CREATE TABLE acl_classes', implode("\n", $executed));
    }

    public function testAbortsWhenTablesAlreadyExist(): void
    {
        $existing = new DbalSchema();
        $this->aclSchema()->addToSchema($existing);
        $connection = $this->connection($existing);
        $connection->expects($this->never())->method('executeStatement');

        $tester = new CommandTester(new InitAclCommand($connection, $this->aclSchema()));

        $this->assertSame(1, $tester->execute([]));
        $this->assertStringContainsString('Aborting:', $tester->getDisplay());
    }
}
