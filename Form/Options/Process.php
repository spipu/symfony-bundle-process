<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Form\Options;

use Spipu\UiBundle\Form\Options\AbstractOptions;
use Spipu\ProcessBundle\Service\ConfigReader;

class Process extends AbstractOptions
{
    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * ProcessLogStatus constructor.
     * @param ConfigReader $configReader
     */
    public function __construct(
        ConfigReader $configReader
    ) {
        $this->configReader = $configReader;
    }

    /**
     * Build the list of the available options
     * @return array
     */
    protected function buildOptions(): array
    {
        return $this->configReader->getProcessList();
    }
}
