<?php
namespace Spipu\ProcessBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;
use Spipu\ProcessBundle\Service\Status;
use Spipu\ProcessBundle\Tests\SpipuProcessMock;

class TaskTest extends TestCase
{
    public function testEntity()
    {
        $date = new \DateTime();

        $entity = SpipuProcessMock::getTaskEntity(1);
        $this->assertSame(1, $entity->getId());
        $this->assertSame(0, $entity->getTryNumber());
        $this->assertSame(false, $entity->getCanBeRerunAutomatically());
        $this->assertSame(null, $entity->getTryLastAt());

        $entity->setCode('code');
        $entity->setTryLastMessage('message');
        $entity->setStatus(Status::RUNNING);
        $entity->setCanBeRerunAutomatically(true);
        $entity->setTryLastAt($date);
        $entity->setTryNumber(42);
        $entity->setProgress(43);

        $this->assertSame('code', $entity->getCode());
        $this->assertSame('message', $entity->getTryLastMessage());
        $this->assertSame(Status::RUNNING, $entity->getStatus());
        $this->assertSame(true, $entity->getCanBeRerunAutomatically());
        $this->assertSame($date, $entity->getTryLastAt());
        $this->assertSame(42, $entity->getTryNumber());
        $this->assertSame(43, $entity->getProgress());

        $entity->incrementTry('new message', false);

        $this->assertSame('new message', $entity->getTryLastMessage());
        $this->assertSame(false, $entity->getCanBeRerunAutomatically());
        $this->assertSame(43, $entity->getTryNumber());
        $this->assertGreaterThan($date, $entity->getTryLastAt());

        $inputs = [
            'firstname' => 'John',
            'lastname'  => 'Doe',
            'test'     => ['foo', 'var'],
        ];

        $inputsAsJson = [];
        foreach ($inputs as $key => $value) {
            if (is_array($value) || is_object($value)) {
                $value = json_encode($value);
            }
            $inputsAsJson[$key] = $value;
        }

        $entity->setInputs(json_encode($inputs));
        $this->assertSame(json_encode($inputs), $entity->getInputs());
        $this->assertSame($inputsAsJson, $entity->getInputsAsJson());

        $entity->setInputs('error');
        $this->assertSame('error', $entity->getInputs());
        $this->assertSame([], $entity->getInputsAsJson());

        $result = $entity->getLogs();
        $this->assertInstanceOf(Collection::class, $result);
        $this->assertSame(0, $result->count());

        $log = SpipuProcessMock::getLogEntity();

        $entity->addLog($log);
        $this->assertSame(1, $result->count());

        $entity->addLog($log);
        $this->assertSame(1, $result->count());

        $entity->removeLog($log);
        $this->assertSame(0, $result->count());
    }
}
