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
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

class AnalyseResult implements StepInterface
{
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): int
    {
        $result = $parameters->get('result');
        if (!is_array($result) || !array_key_exists('content', $result)) {
            throw new StepException('Invalid result: ' . print_r($result, true));
        }

        $content = json_decode($result['content'], true);
        if ($content === null) {
            throw new StepException('Invalid json result: ' . print_r($result, true));
        }

        if (!array_key_exists('products', $content)) {
            throw new StepException('products key is missing: ' . print_r($result, true));
        }

        $logger->notice(print_r($content['products'], true));

        return count($content['products']);
    }
}
