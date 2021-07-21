<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Entity\Process;

use Spipu\ProcessBundle\Exception\OptionException;

class Options
{
    /**
     * @var array
     */
    private $options = [];

    /**
     * Inputs constructor.
     * @param array $options
     * @throws OptionException
     */
    public function __construct(
        array $options
    ) {
        $this->options = $options;
        $this->validateDefinition();
    }

    /**
     * @return void
     * @throws OptionException
     */
    private function validateDefinition(): void
    {
        $booleanKeys = [
            'can_be_put_in_queue',
            'can_be_rerun_automatically',
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
    }

    /**
     * @return bool[]
     */
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
     * @return string[]
     */
    public function getProcessLocks(): array
    {
        return (array) $this->options['process_lock'];
    }
}
