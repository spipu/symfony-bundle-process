<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Spipu\UiBundle\Entity\EntityInterface;
use Spipu\UiBundle\Entity\TimestampableTrait;

/**
 * @ORM\Table(name="spipu_process_task")
 * @ORM\Entity(repositoryClass="Spipu\ProcessBundle\Repository\TaskRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Task implements EntityInterface
{
    use TimestampableTrait;

    /**
     * @var int
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var string
     * @ORM\Column(type="string", length=255)
     */
    private $code;

    /**
     * @var string
     * @ORM\Column(type="text")
     */
    private $inputs;

    /**
     * @var string
     * @ORM\Column(type="string", length=16)
     */
    private $status;

    /**
     * @var Log[]
     * @ORM\OneToMany(targetEntity="Spipu\ProcessBundle\Entity\Log", mappedBy="task")
     */
    private $logs;

    /**
     * @var \DateTimeInterface|null
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $tryLastAt;

    /**
     * @var int
     * @ORM\Column(type="integer")
     */
    private $tryNumber;

    /**
     * @var string|null
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $tryLastMessage;

    /**
     * @var bool
     * @ORM\Column(type="boolean")
     */
    private $canBeRerunAutomatically;

    /**
     * @var int
     * @ORM\Column(type="smallint")
     */
    private $progress = 0;

    /**
     * ProcessTask constructor.
     */
    public function __construct()
    {
        $this->logs = new ArrayCollection();
        $this->setTryNumber(0);
        $this->setCanBeRerunAutomatically(false);
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @return null|string
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string $code
     * @return Task
     */
    public function setCode(string $code): self
    {
        $this->code = $code;

        return $this;
    }

    /**
     * @return null|string
     */
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
            $inputs[$key] = json_encode($input);
        }

        return $inputs;
    }

    /**
     * @param string $inputs
     * @return Task
     */
    public function setInputs(string $inputs): self
    {
        $this->inputs = $inputs;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string $status
     * @return Task
     */
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

    /**
     * @param Log $log
     * @return Task
     */
    public function addLog(Log $log): self
    {
        if (!$this->logs->contains($log)) {
            $this->logs[] = $log;
            $log->setTask($this);
        }

        return $this;
    }

    /**
     * @param Log $log
     * @return Task
     */
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

    /**
     * @return \DateTimeInterface|null
     */
    public function getTryLastAt(): ?\DateTimeInterface
    {
        return $this->tryLastAt;
    }

    /**
     * @param \DateTimeInterface|null $tryLastAt
     * @return Task
     */
    public function setTryLastAt(?\DateTimeInterface $tryLastAt): self
    {
        $this->tryLastAt = $tryLastAt;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getTryNumber(): ?int
    {
        return $this->tryNumber;
    }

    /**
     * @param int $tryNumber
     * @return Task
     */
    public function setTryNumber(int $tryNumber): self
    {
        $this->tryNumber = $tryNumber;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getTryLastMessage(): ?string
    {
        return $this->tryLastMessage;
    }

    /**
     * @param null|string $tryLastMessage
     * @return Task
     */
    public function setTryLastMessage(?string $tryLastMessage): self
    {
        $this->tryLastMessage = $tryLastMessage;

        return $this;
    }

    /**
     * @return bool|null
     */
    public function getCanBeRerunAutomatically(): ?bool
    {
        return $this->canBeRerunAutomatically;
    }

    /**
     * @param bool $canBeRerunAutomatically
     * @return Task
     */
    public function setCanBeRerunAutomatically(bool $canBeRerunAutomatically): self
    {
        $this->canBeRerunAutomatically = $canBeRerunAutomatically;

        return $this;
    }

    /**
     * @param string $message
     * @param bool $canBeRerunAutomatically
     * @return Task
     */
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

    /**
     * @return int
     */
    public function getProgress(): int
    {
        return $this->progress;
    }

    /**
     * @param int $progress
     * @return Task
     */
    public function setProgress(int $progress): self
    {
        $this->progress = $progress;

        return $this;
    }
}
