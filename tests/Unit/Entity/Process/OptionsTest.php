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
        return new Options($description);
    }

    public function testConvert()
    {
        $options = self::getOptions($this, ['can_be_put_in_queue' => 1, 'can_be_rerun_automatically' => 0, 'process_lock_on_failed' => 0, 'process_lock' => [], 'needed_role' => null, 'automatic_report' => false,]);
        $this->assertSame(true, $options->getOptions()['can_be_put_in_queue']);
        $this->assertSame(false, $options->getOptions()['can_be_rerun_automatically']);
        $this->assertSame(false, $options->getOptions()['process_lock_on_failed']);
        $this->assertSame(false, $options->getOptions()['automatic_report']);
    }

    public function testOptionsOk()
    {
        $options = self::getOptions($this, ['can_be_put_in_queue' => false, 'can_be_rerun_automatically' => false, 'process_lock_on_failed' => true, 'process_lock' => [], 'needed_role' => null, 'automatic_report' => false,]);
        $this->assertSame(false, $options->canBePutInQueue());
        $this->assertSame(false, $options->canBeRerunAutomatically());
        $this->assertSame([], $options->getProcessLocks());
        $this->assertTrue($options->canProcessLockOnFailed());
        $this->assertNull($options->getNeededRole());
        $this->assertFalse($options->hasAutomaticReport());

        $options = self::getOptions($this, ['can_be_put_in_queue' => true, 'can_be_rerun_automatically' => false, 'process_lock_on_failed' => true, 'process_lock' => 'foo', 'needed_role' => null, 'automatic_report' => false,]);
        $this->assertSame(true, $options->canBePutInQueue());
        $this->assertSame(false, $options->canBeRerunAutomatically());
        $this->assertSame(['foo'], $options->getProcessLocks());
        $this->assertTrue($options->canProcessLockOnFailed());
        $this->assertNull($options->getNeededRole());
        $this->assertFalse($options->hasAutomaticReport());

        $options = self::getOptions($this, ['can_be_put_in_queue' => true, 'can_be_rerun_automatically' => true, 'process_lock_on_failed' => true, 'process_lock' => ['foo', 'bar'], 'needed_role' => null, 'automatic_report' => false,]);
        $this->assertSame(true, $options->canBePutInQueue());
        $this->assertSame(true, $options->canBeRerunAutomatically());
        $this->assertSame(['foo', 'bar'], $options->getProcessLocks());
        $this->assertTrue($options->canProcessLockOnFailed());
        $this->assertNull($options->getNeededRole());
        $this->assertFalse($options->hasAutomaticReport());

        $options = self::getOptions($this, ['can_be_put_in_queue' => true, 'can_be_rerun_automatically' => true, 'process_lock_on_failed' => false, 'process_lock' => ['foo', 'bar'], 'needed_role' => null, 'automatic_report' => false,]);
        $this->assertSame(true, $options->canBePutInQueue());
        $this->assertSame(true, $options->canBeRerunAutomatically());
        $this->assertSame(['foo', 'bar'], $options->getProcessLocks());
        $this->assertFalse($options->canProcessLockOnFailed());
        $this->assertNull($options->getNeededRole());
        $this->assertFalse($options->hasAutomaticReport());

        $options = self::getOptions($this, ['can_be_put_in_queue' => true, 'can_be_rerun_automatically' => true, 'process_lock_on_failed' => false, 'process_lock' => ['foo', 'bar'], 'needed_role' => '', 'automatic_report' => false,]);
        $this->assertSame(true, $options->canBePutInQueue());
        $this->assertSame(true, $options->canBeRerunAutomatically());
        $this->assertSame(['foo', 'bar'], $options->getProcessLocks());
        $this->assertFalse($options->canProcessLockOnFailed());
        $this->assertNull($options->getNeededRole());
        $this->assertFalse($options->hasAutomaticReport());

        $options = self::getOptions($this, ['can_be_put_in_queue' => true, 'can_be_rerun_automatically' => true, 'process_lock_on_failed' => true, 'process_lock' => ['foo', 'bar'], 'needed_role' => 'ROLE_FOO', 'automatic_report' => false,]);
        $this->assertSame(true, $options->canBePutInQueue());
        $this->assertSame(true, $options->canBeRerunAutomatically());
        $this->assertSame(['foo', 'bar'], $options->getProcessLocks());
        $this->assertTrue($options->canProcessLockOnFailed());
        $this->assertSame('ROLE_FOO', $options->getNeededRole());
        $this->assertFalse($options->hasAutomaticReport());

        $options = self::getOptions($this, ['can_be_put_in_queue' => false, 'can_be_rerun_automatically' => false, 'process_lock_on_failed' => true, 'process_lock' => [], 'needed_role' => null, 'automatic_report' => true,]);
        $this->assertSame(false, $options->canBePutInQueue());
        $this->assertSame(false, $options->canBeRerunAutomatically());
        $this->assertSame([], $options->getProcessLocks());
        $this->assertTrue($options->canProcessLockOnFailed());
        $this->assertNull($options->getNeededRole());
        $this->assertTrue($options->hasAutomaticReport());
    }

    public function testOptionsKo()
    {
        $this->expectException(OptionException::class);
        self::getOptions($this, ['can_be_put_in_queue' => false, 'can_be_rerun_automatically' => true, 'process_lock_on_failed' => true, 'process_lock' => [], 'needed_role' => null, 'automatic_report' => false,]);
    }
}