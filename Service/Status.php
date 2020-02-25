<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Service;

class Status
{
    const CREATED  = 'created';
    const RUNNING  = 'running';
    const FINISHED = 'finished';
    const FAILED   = 'failed';

    /**
     * List of available statuses
     * @return string[]
     */
    public function getStatuses(): array
    {
        return [
            static::CREATED,
            static::RUNNING,
            static::FINISHED,
            static::FAILED,
        ];
    }

    /**
     * @return string[]
     */
    public function getExecutableStatuses(): array
    {
        return [
            static::CREATED,
            static::FAILED
        ];
    }

    /**
     * @param string $status
     * @return bool
     */
    public function canRerun(string $status): bool
    {
        return in_array(
            $status,
            $this->getExecutableStatuses()
        );
    }

    /**
     * @param string $status
     * @return bool
     */
    public function canKill(string $status): bool
    {
        return $status === static::RUNNING;
    }

    /**
     * @return string
     */
    public function getCreatedStatus(): string
    {
        return self::CREATED;
    }

    /**
     * @return string
     */
    public function getFinishedStatus(): string
    {
        return self::FINISHED;
    }
}
