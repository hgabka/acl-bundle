<?php

namespace Symfony\Bundle\AclBundle\Tests\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\AclBundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends TestCase
{
    private function process(array ...$configs): array
    {
        return (new Processor())->processConfiguration(new Configuration(), $configs);
    }

    public function testDefaults(): void
    {
        $config = $this->process([]);

        $this->assertNull($config['connection']);
        $this->assertSame('sf_acl_', $config['cache']['prefix']);
        $this->assertTrue($config['voter']['allow_if_object_identity_unavailable']);
        $this->assertSame(
            [
                'class' => 'acl_classes',
                'entry' => 'acl_entries',
                'object_identity' => 'acl_object_identities',
                'object_identity_ancestors' => 'acl_object_identity_ancestors',
                'security_identity' => 'acl_security_identities',
            ],
            $config['tables']
        );
        $this->assertArrayNotHasKey('provider', $config);
    }

    public function testOptionsCanBeOverridden(): void
    {
        $config = $this->process([
            'connection' => 'acl',
            'tables' => ['entry' => 'my_entries'],
            'voter' => ['allow_if_object_identity_unavailable' => false],
            'cache' => ['pool' => 'cache.app', 'prefix' => 'x_'],
        ]);

        $this->assertSame('acl', $config['connection']);
        $this->assertSame('my_entries', $config['tables']['entry']);
        $this->assertSame('acl_classes', $config['tables']['class']);
        $this->assertFalse($config['voter']['allow_if_object_identity_unavailable']);
        $this->assertSame('cache.app', $config['cache']['pool']);
    }

    public function testCacheIdAndPoolAreMutuallyExclusive(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('You cannot set both a cache service id and cache pool');
        $this->process(['cache' => ['id' => 'my.cache', 'pool' => 'cache.app']]);
    }
}
