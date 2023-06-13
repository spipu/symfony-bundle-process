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

namespace Spipu\ProcessBundle\Entity\Process;

use Spipu\ProcessBundle\Exception\OptionException;

class Options
{
    private array $options;

    public function __construct(
        array $options
    ) {
        $this->options = $options;
        $this->validateDefinition();
    }

    private function validateDefinition(): void
    {
        $booleanKeys = [
            'can_be_put_in_queue',
            'can_be_rerun_automatically',
            'process_lock_on_failed',
            'automatic_report',
        ];
        foreach ($booleanKeys as $booleanKey) {
            $this->options[$booleanKey] = (bool) ($this->options[$booleanKey]);
        }

        if (!is_array($this->options['process_lock'])) {
            $this->options['process_lock'] = [$this->options['process_lock']];
        }

        if ($this->canBeRerunAutomatically() && !$this->canBePutInQueue()) {
            throw new OptionException(
                'Invalid process options. can_be_put_in_queue is required when using can_be_rerun_automatically'
            );
        }

        if (!is_null($this->options['needed_role'])) {
            $this->options['needed_role'] = trim($this->options['needed_role']);
            if ($this->options['needed_role'] === '') {
                $this->options['needed_role'] = null;
            }
        }
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Can the process be put in queue in case of error ?
     *
     * @return bool
     */
    public function canBePutInQueue(): bool
    {
        return (bool) $this->options['can_be_put_in_queue'];
    }

    /**
     * Can the process be rerun automatically in case of communication error ?
     *
     * @return bool
     */
    public function canBeRerunAutomatically(): bool
    {
        return (bool) $this->options['can_be_rerun_automatically'];
    }

    /**
     * Can the process be lock on failed ?
     *
     * @return bool
     */
    public function canProcessLockOnFailed(): bool
    {
        return (bool) $this->options['process_lock_on_failed'];
    }

    /**
     * @return string[]
     */
    public function getProcessLocks(): array
    {
        return (array) $this->options['process_lock'];
    }

    public function getNeededRole(): ?string
    {
        return $this->options['needed_role'];
    }

    /**
     * Do we have automatic report
     *
     * @return bool
     */
    public function hasAutomaticReport(): bool
    {
        return (bool) $this->options['automatic_report'];
    }
}
