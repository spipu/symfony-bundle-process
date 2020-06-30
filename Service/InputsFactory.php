<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Service;

use Spipu\ProcessBundle\Entity\Process\Input;
use Spipu\ProcessBundle\Entity\Process\Inputs;
use Spipu\ProcessBundle\Exception\InputException;
use Spipu\UiBundle\Form\Options\AbstractOptions;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InputsFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * InputsFactory constructor.
     * @param ContainerInterface $container
     */
    public function __construct(
        ContainerInterface $container
    ) {
        $this->container = $container;
    }

    /**
     * @param array $definitions
     * @return Inputs
     * @throws InputException
     */
    public function create(array $definitions): Inputs
    {
        $inputs = new Inputs();
        foreach ($definitions as $definition) {
            $inputs->addInput($this->createInput($definition));
        }

        return $inputs;
    }

    /**
     * @param array $definition
     * @return Input
     * @throws InputException
     */
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
            $definition['allowed_mime_types']
        );
    }
}
