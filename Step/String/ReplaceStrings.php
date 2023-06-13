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

namespace Spipu\ProcessBundle\Step\String;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Step\StepInterface;

class ReplaceStrings implements StepInterface
{
    public function execute(ParametersInterface $parameters, LoggerInterface $logger): string
    {
        $replacesStrings = $parameters->get('replace');
        $subject = $parameters->get('subject');

        $return = $subject;
        foreach ($replacesStrings as $search => $replace) {
            $logger->debug(sprintf('Replace %s => %s', $search, $replace));
            $return = str_replace($search, $replace, $return);
        }

        $logger->notice(sprintf('Final string : %s', $return));
        return $return;
    }
}
