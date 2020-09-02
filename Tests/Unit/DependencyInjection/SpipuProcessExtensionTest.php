<?php
namespace Spipu\ProcessBundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Spipu\CoreBundle\DependencyInjection\RolesHierarchyExtensionExtensionInterface;
use Spipu\CoreBundle\Service\RoleDefinitionInterface;
use Spipu\CoreBundle\Tests\SymfonyMock;
use Spipu\ProcessBundle\DependencyInjection\SpipuProcessConfiguration;
use Spipu\ProcessBundle\DependencyInjection\SpipuProcessExtension;
use Spipu\ProcessBundle\Tests\SpipuProcessMock;
use Symfony\Component\DependencyInjection\Extension\ConfigurationExtensionInterface;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;

class SpipuProcessExtensionTest extends TestCase
{
    public function testBase()
    {
        $builder = SymfonyMock::getContainerBuilder($this);

        $extension = new SpipuProcessExtension();

        $this->assertInstanceOf(ExtensionInterface::class, $extension);

        $this->assertSame('spipu_process', $extension->getAlias());

        $this->assertInstanceOf(RolesHierarchyExtensionExtensionInterface::class, $extension);
        $this->assertInstanceOf(RoleDefinitionInterface::class, $extension->getRolesHierarchy());

        $this->assertInstanceOf(ConfigurationExtensionInterface::class, $extension);
        $this->assertInstanceOf(SpipuProcessConfiguration::class, $extension->getConfiguration([], $builder));

        $this->assertFalse($builder->hasParameter('spipu_process'));
        $extension->load([], $builder);
        $this->assertTrue($builder->hasParameter('spipu_process'));
        $this->assertSame([], $builder->getParameter('spipu_process'));
    }

    public function testLoad()
    {
        $configs = [0 => SpipuProcessMock::getConfigurationSampleData()];
        $workflows = SpipuProcessMock::getConfigurationSampleDataBuilt();

        $containerBuilder = SymfonyMock::getContainerBuilder($this);

        $containerBuilder
            ->expects($this->once())
            ->method('setParameter')
            ->with('spipu_process', $workflows);

        $extension = new SpipuProcessExtension();
        $extension->load($configs, $containerBuilder);
    }
}
