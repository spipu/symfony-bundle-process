<?php
namespace Spipu\ProcessBundle\Tests\Unit\Entity\Process;

use PHPUnit\Framework\TestCase;
use Spipu\ProcessBundle\Entity\Process\Options;
use Spipu\ProcessBundle\Exception\OptionException;

class OptionsTest extends TestCase
{
    /**
     * @param TestCase $testCase
     * @param array $description
     * @return Options
     */
    public static function getOptions(TestCase $testCase, array $description = [])
    {
        $options = new Options($description);

        return $options;
    }

    public function testConvert()
    {
        $options = self::getOptions($this, ['can_be_put_in_queue' => 1, 'can_be_rerun_automatically' => 0, 'process_lock' => []]);
        $this->assertSame(true, $options->getOptions()['can_be_put_in_queue']);
        $this->assertSame(false, $options->getOptions()['can_be_rerun_automatically']);
    }

    public function testOptionsOk()
    {
        $options = self::getOptions($this, ['can_be_put_in_queue' => false, 'can_be_rerun_automatically' => false, 'process_lock' => []]);
        $this->assertSame(false, $options->canBePutInQueue());
        $this->assertSame(false, $options->canBeRerunAutomatically());
        $this->assertSame([], $options->getProcessLocks());

        $options = self::getOptions($this, ['can_be_put_in_queue' => true, 'can_be_rerun_automatically' => false, 'process_lock' => 'foo']);
        $this->assertSame(true, $options->canBePutInQueue());
        $this->assertSame(false, $options->canBeRerunAutomatically());
        $this->assertSame(['foo'], $options->getProcessLocks());

        $options = self::getOptions($this, ['can_be_put_in_queue' => true, 'can_be_rerun_automatically' => true, 'process_lock' => ['foo', 'bar']]);
        $this->assertSame(true, $options->canBePutInQueue());
        $this->assertSame(true, $options->canBeRerunAutomatically());
        $this->assertSame(['foo', 'bar'], $options->getProcessLocks());
    }

    public function testOptionsKo()
    {
        $this->expectException(OptionException::class);
        self::getOptions($this, ['can_be_put_in_queue' => false, 'can_be_rerun_automatically' => true, 'process_lock' => []]);
    }
}