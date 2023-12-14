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

namespace Spipu\ProcessBundle\Step\Test;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

class PrepareQuery implements StepInterface
{
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): string
    {
        $query = [
            'agency'   => $parameters->get('agency'),
            'products' => $parameters->get('product_ids'),
        ];

        return json_encode($query);
    }
}
