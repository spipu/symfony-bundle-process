<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Entity\Process;

use Spipu\ProcessBundle\Exception\OptionException;

class Options
{
    /**
     * @var bool[]
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
        foreach ($this->options as $key => $value) {
            $this->options[$key] = ($value ? true : false);
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
        return $this->options['can_be_put_in_queue'];
    }

    /**
     * Can the process be rerun automatically in case of communication error ?
     *
     * @return bool
     */
    public function canBeRerunAutomatically(): bool
    {
        return $this->options['can_be_rerun_automatically'];
    }
}
