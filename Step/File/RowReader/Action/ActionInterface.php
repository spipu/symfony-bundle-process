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
