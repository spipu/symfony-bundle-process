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

use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Step\StepInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConfigReader
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var array
     */
    private $list;

    /**
     * ConfigReader constructor.
     * @param ContainerInterface $container
     */
    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    /**
     * Get the list of the available process
     *
     * @return array
     */
    public function getProcessList(): array
    {
        if (null === $this->list) {
            $this->list = [];
            $processes = $this->container->getParameter('spipu_process');
            foreach ($processes as $process) {
                $this->list[$process['code']] = $process['name'];
            }
        }

        return $this->list;
    }


    /**
     * is the process code exists ?
     * @param string $code
     * @return bool
     */
    public function isProcessExists(string $code): bool
    {
        $list = $this->getProcessList();

        return array_key_exists($code, $list);
    }

    /**
     * Get the process definition
     * @param string $code
     * @return array
     * @throws ProcessException
     */
    public function getProcessDefinition(string $code): array
    {
        if (!$this->isProcessExists($code)) {
            throw new ProcessException('The asked process does not exists');
        }

        $processes = $this->container->getParameter('spipu_process');

        return $processes[$code];
    }

    /**
     * Get the step class from the classname
     * @param string $classname
     * @return StepInterface
     */
    public function getStepClassFromClassname(string $classname): StepInterface
    {
        /** @var StepInterface $stepClass */
        $stepClass = $this->container->get($classname);

        return $stepClass;
    }
}
