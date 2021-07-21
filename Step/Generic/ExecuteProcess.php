<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Step\Generic;

use Spipu\ProcessBundle\Entity\Process\ParametersInterface;
use Spipu\ProcessBundle\Entity\Process\Process;
use Spipu\ProcessBundle\Exception\StepException;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\LoggerInterface;
use Spipu\ProcessBundle\Service\Manager;
use Spipu\ProcessBundle\Service\Url;
use Spipu\ProcessBundle\Step\StepInterface;

class ExecuteProcess implements StepInterface
{
    /**
     * @var Manager
     */
    private $manager;

    /**
     * @var Url
     */
    private $url;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ExecuteProcess constructor.
     * @param Manager $manager
     * @param Url $url
     */
    public function __construct(
        Manager $manager,
        Url $url
    ) {
        $this->manager = $manager;
        $this->url = $url;
    }

    /**
     * @param ParametersInterface $parameters
     * @param LoggerInterface $logger
     * @return mixed
     * @throws ProcessException
     */
    public function execute(ParametersInterface $parameters, LoggerInterface $logger)
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
        } catch (\Exception $e) {
            throw new StepException(
                'An error occurs during the execution of ' . $code .
                ': "' . $e->getMessage() . '", Look at the corresponding log for more details'
            );
        }

        return $result;
    }

    /**
     * @param Process $process
     * @return void
     */
    public function addLogUrl(Process $process): void
    {
        $url = $this->url->getAdminProcessLogUrl($process->getLogId());
        $this->logger->debug(sprintf(' Log: [%s]', $url));
    }
}
