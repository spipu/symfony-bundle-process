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
    /**
     * @var string
     */
    private string $message;

    /**
     * @var string
     */
    private string $level;

    /**
     * @var string|null
     */
    private ?string $link;

    /**
     * @param string $message
     * @param string $level
     * @param string|null $link
     */
    public function __construct(
        string $message,
        string $level,
        ?string $link = null
    ) {
        $this->message = $message;
        $this->level = $level;
        $this->link = $link;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return string
     */
    public function getLevel(): string
    {
        return $this->level;
    }

    /**
     * @return string|null
     */
    public function getLink(): ?string
    {
        return $this->link;
    }
}
