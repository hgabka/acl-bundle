<?php

namespace Symfony\Bundle\AclBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Bundle\AclBundle\Command\SetAclCommand;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Security\Acl\Domain\Acl;
use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\RoleSecurityIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;
use Symfony\Component\Security\Acl\Exception\AclAlreadyExistsException;
use Symfony\Component\Security\Acl\Model\MutableAclInterface;
use Symfony\Component\Security\Acl\Model\MutableAclProviderInterface;
use Symfony\Component\Security\Acl\Permission\MaskBuilder;

class SetAclCommandTest extends TestCase
{
    public function testRoleAceIsInsertedForObjectIdentity(): void
    {
        $acl = $this->createMock(MutableAclInterface::class);
        $provider = $this->createMock(MutableAclProviderInterface::class);
        $provider->expects($this->once())->method('createAcl')->with($this->equalTo(new ObjectIdentity('42', 'Acme\\MyClass')));
        $provider->method('findAcl')->willReturn($acl);
        $provider->expects($this->once())->method('updateAcl')->with($acl);

        $mask = (new MaskBuilder())->add('VIEW')->add('EDIT')->get();
        $acl->expects($this->once())->method('insertObjectAce')->with($this->equalTo(new RoleSecurityIdentity('ROLE_USER')), $mask);
        $acl->expects($this->never())->method('insertClassAce');

        $tester = new CommandTester(new SetAclCommand($provider));
        $status = $tester->execute(['arguments' => ['VIEW', 'EDIT', 'Acme/MyClass:42'], '--role' => ['ROLE_USER']]);

        $this->assertSame(0, $status);
    }

    public function testClassScopeUsesClassAceAndUserIdentity(): void
    {
        $acl = $this->createMock(MutableAclInterface::class);
        $provider = $this->createStub(MutableAclProviderInterface::class);
        $provider->method('findAcl')->willReturn($acl);

        $acl->expects($this->once())->method('insertClassAce')->with(
            $this->equalTo(new UserSecurityIdentity('kevin', 'Symfony\\Component\\Security\\Core\\User\\User')),
            (new MaskBuilder())->add('OWNER')->get()
        );
        $acl->expects($this->never())->method('insertObjectAce');

        $tester = new CommandTester(new SetAclCommand($provider));
        $tester->execute([
            'arguments' => ['OWNER', 'Acme/MyClass:1'],
            '--user' => ['Symfony/Component/Security/Core/User/User:kevin'],
            '--class-scope' => true,
        ]);
    }

    public function testExistingAclIsReused(): void
    {
        $provider = $this->createMock(MutableAclProviderInterface::class);
        $provider->method('createAcl')->willThrowException(new AclAlreadyExistsException());
        $provider->expects($this->once())->method('findAcl')->willReturn($this->createStub(MutableAclInterface::class));
        $provider->expects($this->once())->method('updateAcl');

        $tester = new CommandTester(new SetAclCommand($provider));

        $this->assertSame(0, $tester->execute(['arguments' => ['VIEW', 'A:1'], '--role' => ['ROLE_A']]));
    }

    public function testUserOrRoleIsRequired(): void
    {
        $tester = new CommandTester(new SetAclCommand($this->createStub(MutableAclProviderInterface::class)));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A Role or a User must be specified.');
        $tester->execute(['arguments' => ['VIEW', 'A:1']]);
    }

    public function testUserMustContainClassName(): void
    {
        $tester = new CommandTester(new SetAclCommand($this->createStub(MutableAclProviderInterface::class)));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The user must follow the format');
        $tester->execute(['arguments' => ['VIEW', 'A:1'], '--user' => ['kevin']]);
    }
}
