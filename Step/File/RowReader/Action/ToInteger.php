<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Step\File\RowReader\Action;

use Spipu\ProcessBundle\Exception\RowReaderException;

class ToInteger implements ActionInterface
{
    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'toInteger';
    }

    /**
     * Execute the action
     * @param string|null $value
     * @param array $parameters
     * @return null|string
     * @throws RowReaderException
     */
    public function execute(?string $value, array $parameters = []): ?string
    {
        if ($value === '') {
            $value = null;
        }

        if ($value !== null) {
            $value = preg_replace('/^[\+]?[0]*([0-9]+)$/', '$1', $value);
            if (!preg_match('/^[0-9]+$/', $value)) {
                throw new RowReaderException('Invalid Integer Value: ['.$value.']');
            }
        }

        return $value;
    }
}
