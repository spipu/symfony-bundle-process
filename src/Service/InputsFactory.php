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

use Spipu\ProcessBundle\Entity\Process\Input;
use Spipu\ProcessBundle\Entity\Process\Inputs;
use Spipu\UiBundle\Form\Options\AbstractOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InputsFactory
{
    private ContainerInterface $container;

    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    public function create(array $definitions): Inputs
    {
        $inputs = new Inputs();
        foreach ($definitions as $definition) {
            $inputs->addInput($this->createInput($definition));
        }

        return $inputs;
    }

    private function createInput(array $definition): Input
    {
        $options = null;
        if (array_key_exists('options', $definition)) {
            /** @var AbstractOptions $options */
            $options = $this->container->get($definition['options']);
        }

        return new Input(
            $definition['name'],
            $definition['type'],
            $definition['required'],
            $options,
            $definition['allowed_mime_types'],
            $definition['regexp'],
            $definition['help']
        );
    }
}
