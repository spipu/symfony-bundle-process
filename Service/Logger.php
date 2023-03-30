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
use Stringable;
use Throwable;

/**
 * @SuppressWarnings(PMD.TooManyPublicMethods)
 */
class Logger implements LoggerProcessInterface
{
    private EntityManagerInterface $entityManager;
    private ?MailManager $mailer;
    private array $messages = [];
    private ?ProcessLog $model = null;
    private int $nbSteps = 0;
    private int $currentStep = 0;
    private bool $ignoreInProgress = false;
    private ?LoggerOutputInterface $loggerOutput = null;
    private ?Throwable $lastException = null;

    public function __construct(
        EntityManagerInterface $entityManager,
        MailManager $mailer = null
    ) {
        $this->entityManager = $entityManager;
        $this->mailer = $mailer;
    }

    public function __destruct()
    {
        unset($this->model);
    }

    public function __clone()
    {
        unset($this->model);
        $this->model = null;
    }

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

    public function setLoggerOutput(?LoggerOutputInterface $loggerOutput): void
    {
        $this->loggerOutput = $loggerOutput;
    }

    public function setCurrentStep(int $currentStep, bool $ignoreInProgress): void
    {
        // Set the current step, from 0 to n-1.
        $this->currentStep = $currentStep;
        $this->ignoreInProgress = $ignoreInProgress;
        $this->setProgress(0);
    }

    public function setProgress(int $progressOnCurrentStep): void
    {
        // Set the progress on the current step.
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

    public function setLastException(?Throwable $lastException): void
    {
        $this->lastException = $lastException;
    }

    public function finish(string $status): void
    {
        $this->model->setStatus($status);

        if (($status == Status::FAILED) && ($this->mailer !== null)) {
            $this->warning('A technical alert email will been sent');
            try {
                $this->mailer->sendAlert($this->getModel(), $this->lastException);
            } catch (Exception $e) {
                $this->critical(' => ERROR when sending the email');
                $this->critical((string) $e);
            }
        }

        $this->saveModel();
    }

    private function saveModel(): void
    {
        if ($this->model === null) {
            throw new ProcessException('You must init the logger before using it!');
        }

        $this->model->setContent(json_encode($this->messages));

        try {
            $this->entityManager->flush();
        } catch (Exception $e) {
            echo "FATAL ERROR DURING ENTITY MANAGER FLUSH!!!\n";
            echo "Log Content\n";
            echo "============================\n";
            print_r($this->messages);
            echo "============================\n";

            throw $e;
        }
    }

    public function emergency(string|Stringable $message, array $context = array()): void
    {
        $this->log('emergency', $message, $context);
    }

    public function alert(string|Stringable $message, array $context = array()): void
    {
        $this->log('alert', $message, $context);
    }

    public function critical(string|Stringable $message, array $context = array()): void
    {
        $this->log('critical', $message, $context);
    }

    public function error(string|Stringable $message, array $context = array()): void
    {
        $this->log('error', $message, $context);
    }

    public function warning(string|Stringable $message, array $context = array()): void
    {
        $this->log('warning', $message, $context);
    }

    public function notice(string|Stringable $message, array $context = array()): void
    {
        $this->log('notice', $message, $context);
    }

    public function info(string|Stringable $message, array $context = array()): void
    {
        $this->log('info', $message, $context);
    }

    public function debug(string|Stringable $message, array $context = array()): void
    {
        $this->log('debug', $message, $context);
    }

    public function log($level, string|Stringable $message, array $context = array()): void
    {
        $messageRow = [
            'date'        => (new DateTime())->getTimestamp(),
            'memory'      => memory_get_usage(),
            'memory_peak' => memory_get_peak_usage(),
            'level'       => (string) $level,
            'message'     => (string) mb_convert_encoding($message, 'UTF-8'),
            'context'     => $context,
        ];

        if ($this->loggerOutput) {
            $this->loggerOutput->write($messageRow);
        }

        $this->messages[] = $messageRow;

        $this->saveModel();
    }

    public function getModel(): ?ProcessLog
    {
        return $this->model;
    }

    public function initFromExistingLog(ProcessLog $log): void
    {
        $this->model = $log;
        $this->nbSteps = 1;

        try {
            $messages = json_decode($log->getContent(), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($messages)) {
                $messages = [];
            }
            $this->messages = $messages;
        } catch (Exception $e) {
            $this->messages = [];
        }
    }

    /**
     * @return array
     */
    public function getMessages(): array
    {
        return $this->messages;
    }
}
