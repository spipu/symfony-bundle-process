<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\DependencyInjection;

use Spipu\CoreBundle\DependencyInjection\RolesHierarchyExtensionExtensionInterface;
use Spipu\CoreBundle\Service\RoleDefinitionInterface;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\RoleDefinition;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

final class SpipuProcessExtension extends Extension implements RolesHierarchyExtensionExtensionInterface
{
    /**
     * Get the alias in config file
     * @return string
     */
    public function getAlias(): string
    {
        return 'spipu_process';
    }

    /**
     * @param array $configs
     * @param ContainerBuilder $container
     * @return void
     * @throws \Exception
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $configuration = $this->getConfiguration($configs, $container);
        $configs = $this->processConfiguration($configuration, $configs);

        foreach ($configs as $code => $config) {
            $configs[$code] = $this->prepareConfig($config, $code);
        }

        ksort($configs);

        $container->setParameter('spipu_process', $configs);
    }

    /**
     * @param array $process
     * @param string $processCode
     * @return array
     * @throws ProcessException
     * @SuppressWarnings(PMD.CyclomaticComplexity)
     * @SuppressWarnings(PMD.NPathComplexity)
     */
    private function prepareConfig(array $process, string $processCode): array
    {
        $process['code'] = $processCode;

        foreach ($process['inputs'] as $inputCode => &$input) {
            $input['name'] = $inputCode;
            $this->validateConfigInput($input);
        }

        foreach ($process['steps'] as $stepCode => &$step) {
            $step['code'] = $stepCode;
        }

        return $process;
    }

    /**
     * @param array $input
     * @return void
     * @throws ProcessException
     */
    private function validateConfigInput(array $input): void
    {
        if (count($input['allowed_mime_types']) > 0 && $input['type'] !== 'file') {
            throw new ProcessException('Config Error - allowed_mime_types can be used only with file type');
        }

        if (!empty($input['options']) && $input['type'] === 'file') {
            throw new ProcessException('Config Error - options can not be used with file type');
        }
    }

    /**
     * Get the configuration to use
     * @param array $config
     * @param ContainerBuilder $container
     * @return ConfigurationInterface
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function getConfiguration(array $config, ContainerBuilder $container): ConfigurationInterface
    {
        return new SpipuProcessConfiguration();
    }

    /**
     * @return RoleDefinitionInterface
     */
    public function getRolesHierarchy(): RoleDefinitionInterface
    {
        return new RoleDefinition();
    }
}
