<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Step\File\RowReader\Action;

interface ActionInterface
{
    /**
     * @return string
     */
    public function getCode(): string;

    /**
     * Execute the action
     * @param string|null $value
     * @param array $parameters
     * @return null|string
     */
    public function execute(?string $value, array $parameters = []): ?string;
}
