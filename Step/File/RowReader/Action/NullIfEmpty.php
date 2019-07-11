<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Step\File\RowReader\Action;

class NullIfEmpty implements ActionInterface
{
    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'nullIfEmpty';
    }

    /**
     * Execute the action
     * @param string|null $value
     * @param array $parameters OPTIONAL.
     *
     * @return null|string
     */
    public function execute(?string $value, array $parameters = []): ?string
    {
        if ($value === '') {
            $value = null;
        }

        return $value;
    }
}
