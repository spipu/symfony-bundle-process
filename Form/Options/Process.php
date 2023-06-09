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

namespace Spipu\ProcessBundle\Form\Options;

use Spipu\UiBundle\Form\Options\AbstractOptions;
use Spipu\ProcessBundle\Service\ConfigReader;

class Process extends AbstractOptions
{
    private ConfigReader $configReader;

    public function __construct(
        ConfigReader $configReader
    ) {
        $this->configReader = $configReader;
    }

    protected function buildOptions(): array
    {
        return $this->configReader->getProcessList();
    }
}
