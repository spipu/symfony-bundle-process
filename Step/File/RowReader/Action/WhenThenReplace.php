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

namespace Spipu\ProcessBundle\Step\File\RowReader\Action;

class WhenThenReplace implements ActionInterface
{
    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'whenThenReplace';
    }

    /**
     * Execute the action
     * @param string|null $value
     * @param array $parameters
     * @return null|string
     */
    public function execute(?string $value, array $parameters = []): ?string
    {
        if (in_array($value, $parameters['when'])) {
            $value = $parameters['then'];
        } elseif (array_key_exists('else', $parameters)) {
            $value = $parameters['else'];
        }

        return $value;
    }
}
