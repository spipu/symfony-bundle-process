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
        $options = self::getOptions(
            $this,
            [
                'can_be_put_in_queue' => false,
                'can_be_rerun_automatically' => false,
                'option_1' => 0,
                'option_2' => false,
                'option_3' => 1,
                'option_4' => true,
            ]
        );
        $this->assertSame(false, $options->getOptions()['option_1']);
        $this->assertSame(false, $options->getOptions()['option_2']);
        $this->assertSame(true, $options->getOptions()['option_3']);
        $this->assertSame(true, $options->getOptions()['option_4']);
    }

    public function testOptionsOk()
    {
        $options = self::getOptions($this, ['can_be_put_in_queue' => false, 'can_be_rerun_automatically' => false]);
        $this->assertSame(false, $options->canBePutInQueue());
        $this->assertSame(false, $options->canBeRerunAutomatically());

        $options = self::getOptions($this, ['can_be_put_in_queue' => true, 'can_be_rerun_automatically' => false]);
        $this->assertSame(true, $options->canBePutInQueue());
        $this->assertSame(false, $options->canBeRerunAutomatically());

        $options = self::getOptions($this, ['can_be_put_in_queue' => true, 'can_be_rerun_automatically' => true]);
        $this->assertSame(true, $options->canBePutInQueue());
        $this->assertSame(true, $options->canBeRerunAutomatically());
    }

    public function testOptionsKo()
    {
        $this->expectException(OptionException::class);
        self::getOptions($this, ['can_be_put_in_queue' => false, 'can_be_rerun_automatically' => true]);
    }
}