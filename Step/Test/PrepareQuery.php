<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Step\Test;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

class PrepareQuery implements StepInterface
{
    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return string
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): string
    {
        $query = [
            'agency' => $parameters->get('agency'),
            'products' => $parameters->get('product_ids'),
        ];

        return json_encode($query);
    }
}
