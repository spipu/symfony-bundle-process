<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Service;

use Spipu\ConfigurationBundle\Exception\ConfigurationException;
use Spipu\ConfigurationBundle\Service\Manager as ConfigurationManager;
use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Exception\ProcessException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MainParameters implements ParametersInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ConfigurationManager
     */
    private $configurationManager;

    /**
     * Parameters constructor.
     * @param ContainerInterface $container
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(
        ContainerInterface $container,
        ConfigurationManager $configurationManager
    ) {
        $this->container = $container;
        $this->configurationManager = $configurationManager;
    }

    /**
     * @param ParametersInterface $parentParameters
     * @return void
     * @throws \Exception
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function setParentParameters(ParametersInterface $parentParameters): void
    {
        throw new ProcessException('You may not set a parent here !');
    }

    /**
     * Get a value
     * @param string $code
     * @return mixed
     * @throws ConfigurationException
     */
    public function get(string $code)
    {
        if (preg_match('/^configuration\(([^\)]+)\)$/', $code, $match)) {
            return $this->configurationManager->get($match[1]);
        }
        return $this->container->getParameter($code);
    }

    /**
     * Set a value
     * @param string $code
     * @param mixed $value
     * @return void
     * @throws \Exception
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function set(string $code, $value): void
    {
        throw new ProcessException('You may not set a value here !');
    }

    /**
     * Set a default value
     * @param string $code
     * @param mixed $value
     * @return void
     * @throws \Exception
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function setDefaultValue(string $code, $value): void
    {
        throw new ProcessException('You may not set a default value here !');
    }
}
