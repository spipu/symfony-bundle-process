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

namespace Spipu\ProcessBundle\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Spipu\UiBundle\Entity\EntityInterface;
use Spipu\UiBundle\Entity\TimestampableInterface;
use Spipu\UiBundle\Entity\TimestampableTrait;

#[ORM\Entity(repositoryClass: 'Spipu\ProcessBundle\Repository\TaskRepository')]
#[ORM\Table(name: "spipu_process_task")]
#[ORM\HasLifecycleCallbacks]
class Task implements EntityInterface, TimestampableInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $code = null;

    #[ORM\Column(type: "text")]
    private ?string $inputs = null;

    #[ORM\Column(length: 16)]
    private ?string $status = null;

    #[ORM\OneToMany(mappedBy: "task", targetEntity: "Spipu\ProcessBundle\Entity\Log")]
    private Collection $logs;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?DateTimeInterface $scheduledAt = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?DateTimeInterface $executedAt = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?DateTimeInterface $tryLastAt = null;

    #[ORM\Column]
    private ?int $tryNumber = 0;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $tryLastMessage = null;

    #[ORM\Column]
    private ?bool $canBeRerunAutomatically = null;

    #[ORM\Column(type: "smallint")]
    private int $progress = 0;

    #[ORM\Column(nullable: true)]
    private ?int $pidValue = null;

    #[ORM\Column(type: "datetime", nullable: true)]
    private ?DateTimeInterface $pidLastSeen = null;

    public function __construct()
    {
        $this->logs = new ArrayCollection();
        $this->setTryNumber(0);
        $this->setCanBeRerunAutomatically(false);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getInputs(): ?string
    {
        return $this->inputs;
    }

    /**
     * @return string[]
     */
    public function getInputsAsJson(): array
    {
        $inputs = json_decode($this->inputs, true);
        if (!is_array($inputs)) {
            return [];
        }

        foreach ($inputs as $key => $input) {
            if (is_array($input) || is_object($input)) {
                $inputs[$key] = json_encode($input);
            }
        }

        return $inputs;
    }

    public function setInputs(string $inputs): self
    {
        $this->inputs = $inputs;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection|Log[]
     */
    public function getLogs(): Collection
    {
        return $this->logs;
    }

    public function addLog(Log $log): self
    {
        if (!$this->logs->contains($log)) {
            $this->logs[] = $log;
            $log->setTask($this);
        }

        return $this;
    }

    public function removeLog(Log $log): self
    {
        if ($this->logs->contains($log)) {
            $this->logs->removeElement($log);
            // Set the owning side to null (unless already changed).
            if ($log->getTask() === $this) {
                $log->setTask(null);
            }
        }

        return $this;
    }

    public function getScheduledAt(): ?DateTimeInterface
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?DateTimeInterface $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;
        return $this;
    }

    public function getExecutedAt(): ?DateTimeInterface
    {
        return $this->executedAt;
    }

    public function setExecutedAt(?DateTimeInterface $executedAt): self
    {
        $this->executedAt = $executedAt;
        return $this;
    }

    public function getTryLastAt(): ?DateTimeInterface
    {
        return $this->tryLastAt;
    }

    public function setTryLastAt(?DateTimeInterface $tryLastAt): self
    {
        $this->tryLastAt = $tryLastAt;

        return $this;
    }

    public function getTryNumber(): ?int
    {
        return $this->tryNumber;
    }

    public function setTryNumber(int $tryNumber): self
    {
        $this->tryNumber = $tryNumber;

        return $this;
    }

    public function getTryLastMessage(): ?string
    {
        return $this->tryLastMessage;
    }

    public function setTryLastMessage(?string $tryLastMessage): self
    {
        $this->tryLastMessage = $tryLastMessage;

        return $this;
    }

    public function getCanBeRerunAutomatically(): ?bool
    {
        return $this->canBeRerunAutomatically;
    }

    public function setCanBeRerunAutomatically(bool $canBeRerunAutomatically): self
    {
        $this->canBeRerunAutomatically = $canBeRerunAutomatically;

        return $this;
    }

    public function incrementTry(string $message, bool $canBeRerunAutomatically): self
    {
        $message = mb_substr($message, 0, 255);
        $this
            ->setTryLastAt(new DateTime())
            ->setTryLastMessage($message)
            ->setTryNumber($this->getTryNumber() + 1)
            ->setCanBeRerunAutomatically($canBeRerunAutomatically);

        return $this;
    }

    public function getProgress(): int
    {
        return $this->progress;
    }

    public function setProgress(int $progress): self
    {
        $this->progress = $progress;

        return $this;
    }

    public function getPidValue(): ?int
    {
        return $this->pidValue;
    }

    public function setPidValue(?int $pidValue): self
    {
        $this->pidValue = $pidValue;

        return $this;
    }

    public function getPidLastSeen(): ?DateTimeInterface
    {
        return $this->pidLastSeen;
    }

    public function setPidLastSeen(?DateTimeInterface $pidLastSeen): self
    {
        $this->pidLastSeen = $pidLastSeen;

        return $this;
    }
}
