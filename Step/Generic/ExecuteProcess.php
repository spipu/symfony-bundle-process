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

namespace Spipu\ProcessBundle\Step\Generic;

use Exception;
use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Entity\Process\Process;
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Service\Manager;
use Spipu\ProcessBundle\Service\Url;
use Spipu\ProcessBundle\Step\StepInterface;

class ExecuteProcess implements StepInterface
{
    private Manager $manager;
    private Url $url;
    private LoggerInterface $logger;

    public function __construct(
        Manager $manager,
        Url $url
    ) {
        $this->manager = $manager;
        $this->url = $url;
    }

    public function execute(ParametersInterface $parameters, LoggerInterface $logger): mixed
    {
        $this->logger = $logger;

        $definition = $parameters->get('process');
        if (!is_array($definition) || !array_key_exists('code', $definition)) {
            throw new StepException('The process parameter must be an array, with a code value');
        }

        $code = $definition['code'];
        $inputs = (array_key_exists('inputs', $definition) ? $definition['inputs'] : []);

        if (!is_array($inputs)) {
            throw new StepException('The inputs must be an array');
        }

        $process = $this->manager->load($code);
        foreach ($inputs as $key => $value) {
            $process->getInputs()->set($key, $value);
        }

        try {
            $result = $this->manager->execute($process, [$this, 'addLogUrl']);
        } catch (Exception $e) {
            throw new StepException(
                'An error occurs during the execution of ' . $code .
                ': "' . $e->getMessage() . '", Look at the corresponding log for more details'
            );
        }

        return $result;
    }

    public function addLogUrl(Process $process): void
    {
        $url = $this->url->getLogUrl($process->getLogId());
        $this->logger->debug(sprintf(' Log: [%s]', $url));
    }
}
