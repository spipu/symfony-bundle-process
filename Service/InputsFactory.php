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
        foreach ($definitions as $key => $definition) {
            $inputs->addInput($this->createInput($key, $definition));
        }

        return $inputs;
    }

    /**
     * @param string $key
     * @param array $definition
     * @return Input
     * @throws InputException
     */
    private function createInput(string $key, array $definition): Input
    {
        $options = null;

        if (array_key_exists('options', $definition)) {
            /** @var AbstractOptions $options */
            $options = $this->container->get($definition['options']);
        }

        return new Input($key, $definition['type'], $options);
    }
}
