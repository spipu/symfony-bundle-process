<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Service;

use Spipu\ProcessBundle\Entity\Process\Inputs;
use Spipu\ProcessBundle\Exception\InputException;
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
        $inputs = new Inputs($definitions);

        return $inputs;
    }
}
