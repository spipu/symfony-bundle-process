<?php

/**
 * This file is part of a Spipu Bundle
 *
 * (c) Laurent Minguet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spipu\ProcessBundle\Service;

use Exception;
use Spipu\ConfigurationBundle\Service\ConfigurationManager;
use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Exception\ProcessException;
use Symfony\Component\DependencyInjection\ContainerInterface;

class MainParameters implements ParametersInterface
{
    private ContainerInterface $container;
    private ConfigurationManager $configurationManager;

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
     * @throws Exception
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function setParentParameters(ParametersInterface $parentParameters): void
    {
        throw new ProcessException('You may not set a parent here !');
    }

    public function get(string $code): mixed
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
     * @throws Exception
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function set(string $code, mixed $value): void
    {
        throw new ProcessException('You may not set a value here !');
    }

    /**
     * Set a default value
     * @param string $code
     * @param mixed $value
     * @return void
     * @throws Exception
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function setDefaultValue(string $code, mixed $value): void
    {
        throw new ProcessException('You may not set a default value here !');
    }
}
