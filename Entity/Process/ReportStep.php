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

namespace Spipu\ProcessBundle\Entity\Process;

class ReportStep
{
    private string $message;
    private string $level;
    private ?string $link;

    public function __construct(
        string $message,
        string $level,
        ?string $link = null
    ) {
        $this->message = $message;
        $this->level = $level;
        $this->link = $link;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getLink(): ?string
    {
        return $this->link;
    }
}
