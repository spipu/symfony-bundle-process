<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Exception;

class StepException extends ProcessException
{
    /**
     * Can we rerun the process automatically after this error ?
     * @return bool
     */
    public function canBeRerunAutomatically(): bool
    {
        return false;
    }
}
