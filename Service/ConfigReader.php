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
    private ContainerInterface $container;
    private ?array $list = null;

    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

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

    public function isProcessExists(string $code): bool
    {
        $list = $this->getProcessList();

        return array_key_exists($code, $list);
    }

    public function getProcessDefinition(string $code): array
    {
        if (!$this->isProcessExists($code)) {
            throw new ProcessException('The asked process does not exists');
        }

        $processes = $this->container->getParameter('spipu_process');

        return $processes[$code];
    }

    public function getStepClassFromClassname(string $classname): StepInterface
    {
        /** @var StepInterface $stepClass */
        $stepClass = $this->container->get($classname);

        return $stepClass;
    }
}
