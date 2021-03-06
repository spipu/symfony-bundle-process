<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Step\File\RowReader\Action;

class ToFloat implements ActionInterface
{
    const DEFAULT_DECIMAL = 4;
    const DEFAULT_SEPARATOR = '.';

    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'toFloat';
    }

    /**
     * Execute the action
     * @param string|null $value
     * @param array $parameters
     * @return null|string
     */
    public function execute(?string $value, array $parameters = []): ?string
    {
        if (!isset($parameters['decimal'])) {
            $parameters['decimal'] = self::DEFAULT_DECIMAL;
        }

        if (!isset($parameters['separator'])) {
            $parameters['separator'] = self::DEFAULT_SEPARATOR;
        }

        $decimalPart = substr($value, -1 * $parameters['decimal'], $parameters['decimal']);
        $integerPart = substr($value, 0, strlen($value) - strlen(($decimalPart)));

        return $integerPart . $parameters['separator'] . $decimalPart;
    }
}
