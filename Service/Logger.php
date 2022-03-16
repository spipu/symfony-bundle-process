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

namespace Spipu\ProcessBundle\Service;

use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Spipu\ProcessBundle\Entity\Log as ProcessLog;
use Spipu\ProcessBundle\Entity\Task as ProcessTask;
use Spipu\ProcessBundle\Exception\ProcessException;

/**
 * @SuppressWarnings(PMD.TooManyPublicMethods)
 */
class Logger implements LoggerProcessInterface
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var array
     */
    private $messages;

    /**
     * @var ProcessLog|null
     */
    private $model;

    /**
     * @var MailManager
     */
    private $mailer;

    /**
     * @var int
     */
    private $nbSteps;

    /**
     * @var int
     */
    private $currentStep;

    /**
     * @var bool
     */
    private $ignoreInProgress;

    /**
     * @var LoggerOutputInterface|null
     */
    private $loggerOutput;

    /**
     * Logger constructor.
     * @param EntityManagerInterface $entityManager
     * @param MailManager|null $mailer
     */
    public function __construct(
        EntityManagerInterface $entityManager,
        MailManager $mailer = null
    ) {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
    }

    /**
     * If destructed, do not transfer the model
     */
    public function __destruct()
    {
        unset($this->model);
    }

    /**
     * If cloned, do not transfer the model
     * @return void
     */
    public function __clone()
    {
        unset($this->model);
        $this->model = null;
    }

    /**
     * Init the logger for a new process
     * @param string $processCode
     * @param int $nbSteps
     * @param ProcessTask|null $task
     * @return int
     */
    public function init(string $processCode, int $nbSteps, ?ProcessTask $task): int
    {
        $this->messages = [];
        $this->model = new ProcessLog();
        $this->model->setCode($processCode);
        $this->model->setStatus(Status::RUNNING);
        $this->model->setTask($task);

        $this->entityManager->persist($this->model);

        $this->nbSteps = $nbSteps;
        $this->setCurrentStep(0, false);

        $this->info(sprintf('Process Started [%s]', $processCode));

        return $this->model->getId();
    }

    /**
     * @param LoggerOutputInterface|null $loggerOutput
     * @return void
     */
    public function setLoggerOutput(?LoggerOutputInterface $loggerOutput): void
    {
        $this->loggerOutput = $loggerOutput;
    }

    /**
     * Set the current step, from 0 to n-1
     * @param int $currentStep
     * @param bool $ignoreInProgress
     * @return void
     */
    public function setCurrentStep(int $currentStep, bool $ignoreInProgress): void
    {
        $this->currentStep = $currentStep;
        $this->ignoreInProgress = $ignoreInProgress;
        $this->setProgress(0);
    }

    /**
     * Set the progress on the current step
     * @param int $progressOnCurrentStep
     * @return void
     */
    public function setProgress(int $progressOnCurrentStep): void
    {
        if ($this->ignoreInProgress) {
            $progressOnCurrentStep = 0;
        }

        $progress = (0.01 * (float) $progressOnCurrentStep) + (float) $this->currentStep;
        $progress = 100. * $progress / ((float) $this->nbSteps);

        $this->model->setProgress((int) $progress);
        if ($this->model->getTask()) {
            $this->model->getTask()->setProgress((int) $progress);
        }
    }

    /**
     * Finish the logger for the current process
     * @param string $status
     * @return void
     */
    public function finish(string $status): void
    {
        $this->model->setStatus($status);

        if (($status == Status::FAILED) && ($this->mailer !== null)) {
            $this->warning('A technical alert email will been sent');
            try {
                $this->mailer->sendAlert($this->getModel());
            } catch (Exception $e) {
                $this->critical(' => ERROR when sending the email');
                $this->critical((string) $e);
            }
        }

        $this->saveModel();
    }

    /**
     * save the model
     * @return void
     * @throws Exception
     */
    private function saveModel()
    {
        if ($this->model === null) {
            throw new ProcessException('You must init the logger before using it!');
        }

        $this->model->setContent(json_encode($this->messages));

        try {
            $this->entityManager->flush();
        } catch (Exception $e) {
            echo "FATAL ERROR DURING ENTITY MANAGER FLUSH!!!";
            echo "Log Content";
            echo "============================";
            print_r($this->messages);
            echo "============================";

            throw $e;
        }
    }

    /**
     * System is unusable.
     *
     * @param mixed $message
     * @param array $context
     *
     * @return void
     */
    public function emergency($message, array $context = array())
    {
        $this->log('emergency', $message, $context);
    }

    /**
     * Action must be taken immediately.
     *
     * Example: Entire website down, database unavailable, etc. This should
     * trigger the SMS alerts and wake you up.
     *
     * @param mixed $message
     * @param array $context
     *
     * @return void
     */
    public function alert($message, array $context = array())
    {
        $this->log('alert', $message, $context);
    }

    /**
     * Critical conditions.
     *
     * @param mixed $message
     * @param array $context
     *
     * @return void
     */
    public function critical($message, array $context = array())
    {
        $this->log('critical', $message, $context);
    }

    /**
     * Runtime errors that do not require immediate action but should typically
     * be logged and monitored.
     *
     * @param mixed $message
     * @param array $context
     *
     * @return void
     */
    public function error($message, array $context = array())
    {
        $this->log('error', $message, $context);
    }

    /**
     * Exceptional occurrences that are not errors.
     *
     * Example: Use of deprecated APIs, poor use of an API, undesirable things
     * that are not necessarily wrong.
     *
     * @param mixed $message
     * @param array $context
     *
     * @return void
     */
    public function warning($message, array $context = array())
    {
        $this->log('warning', $message, $context);
    }

    /**
     * Normal but significant events.
     *
     * @param mixed $message
     * @param array $context
     *
     * @return void
     */
    public function notice($message, array $context = array())
    {
        $this->log('notice', $message, $context);
    }

    /**
     * Interesting events.
     *
     * Example: User logs in, SQL logs.
     *
     * @param mixed $message
     * @param array $context
     *
     * @return void
     */
    public function info($message, array $context = array())
    {
        $this->log('info', $message, $context);
    }

    /**
     * Detailed debug information.
     *
     * @param mixed $message
     * @param array $context
     *
     * @return void
     */
    public function debug($message, array $context = array())
    {
        $this->log('debug', $message, $context);
    }

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed $level
     * @param mixed $message
     * @param array $context
     *
     * @return void
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function log($level, $message, array $context = array())
    {
        $messageRow = [
            'date'        => (new DateTime())->getTimestamp(),
            'memory'      => memory_get_usage(),
            'memory_peak' => memory_get_peak_usage(),
            'level'       => (string) $level,
            'message'     => (string) mb_convert_encoding($message, 'UTF-8'),
        ];

        if ($this->loggerOutput) {
            $this->loggerOutput->write($messageRow);
        }

        $this->messages[] = $messageRow;

        $this->saveModel();
    }

    /**
     * @return ProcessLog|null
     */
    public function getModel(): ?ProcessLog
    {
        return $this->model;
    }

    /**
     * @param ProcessLog $log
     * @return void
     */
    public function initFromExistingLog(ProcessLog $log): void
    {
        $this->model = $log;
        $this->nbSteps = 1;

        try {
            $this->messages = json_decode($log->getContent(), true);
            if (!is_array($this->messages)) {
                $this->messages = [];
            }
        } catch (Exception $e) {
            $this->messages = [];
        }
    }
}
