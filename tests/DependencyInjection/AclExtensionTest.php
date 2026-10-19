<?php

namespace Symfony\Bundle\AclBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\AclBundle\Command\InitAclCommand;
use Symfony\Bundle\AclBundle\Command\SetAclCommand;
use Symfony\Bundle\AclBundle\DependencyInjection\AclExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Security\Acl\Domain\PsrAclCache;

class AclExtensionTest extends TestCase
{
    private function load(array $config = []): ContainerBuilder
    {
        $container = new ContainerBuilder();
        (new AclExtension())->load([$config], $container);

        return $container;
    }

    public function testDefaultsUseTheDbalProvider(): void
    {
        $container = $this->load();

        $this->assertTrue($container->hasDefinition('security.acl.dbal.schema'));
        $this->assertSame('acl_classes', $container->getParameter('security.acl.dbal.class_table_name'));
        $this->assertSame('acl_entries', $container->getParameter('security.acl.dbal.entry_table_name'));
        $this->assertSame('acl_object_identities', $container->getParameter('security.acl.dbal.oid_table_name'));
        $this->assertSame('acl_object_identity_ancestors', $container->getParameter('security.acl.dbal.oid_ancestors_table_name'));
        $this->assertSame('acl_security_identities', $container->getParameter('security.acl.dbal.sid_table_name'));
    }

    public function testConsoleCommandsAreRegistered(): void
    {
        $container = $this->load();

        $this->assertTrue($container->hasDefinition(InitAclCommand::class));
        $this->assertTrue($container->hasDefinition(SetAclCommand::class));
        $this->assertSame(
            [['command' => 'acl:set']],
            $container->getDefinition(SetAclCommand::class)->getTag('console.command')
        );
    }

    public function testVoterGetsTheObjectIdentityFlag(): void
    {
        $this->assertContains(false, $this->load(['voter' => ['allow_if_object_identity_unavailable' => false]])->getDefinition('security.acl.voter.basic_permissions')->getArguments());
        $this->assertContains(true, $this->load()->getDefinition('security.acl.voter.basic_permissions')->getArguments());
    }

    public function testConnectionAlias(): void
    {
        $container = $this->load(['connection' => 'acl']);

        $this->assertSame('doctrine.dbal.acl_connection', (string) $container->getAlias('security.acl.dbal.connection'));
        $tag = $container->getDefinition('security.acl.dbal.schema_listener')->getTag('doctrine.event_listener');
        $this->assertSame('acl', $tag[0]['connection']);
        $this->assertSame('postGenerateSchema', $tag[0]['event']);
    }

    public function testCustomProviderSkipsDbalServices(): void
    {
        $container = $this->load(['provider' => 'app.acl_provider']);

        $this->assertSame('app.acl_provider', (string) $container->getAlias('security.acl.provider'));
        $this->assertFalse($container->hasDefinition('security.acl.dbal.schema'));
        $this->assertFalse($container->hasParameter('security.acl.dbal.class_table_name'));
    }

    public function testCacheServiceId(): void
    {
        $container = $this->load(['cache' => ['id' => 'my.cache']]);

        $this->assertSame('my.cache', (string) $container->getAlias('security.acl.cache'));
    }

    public function testCachePool(): void
    {
        $container = $this->load(['cache' => ['pool' => 'cache.app', 'prefix' => 'p_']]);

        $this->assertSame('security.acl.cache.psr', (string) $container->getAlias('security.acl.cache'));
        $definition = $container->getDefinition('security.acl.cache.psr');
        $this->assertSame(PsrAclCache::class, $definition->getClass());
        $this->assertSame('cache.app', (string) $definition->getArgument(0));
        $this->assertSame('p_', $definition->getArgument(2));
    }

    public function testNamespace(): void
    {
        $this->assertSame('http://symfony.com/schema/dic/acl', (new AclExtension())->getNamespace());
    }
}
