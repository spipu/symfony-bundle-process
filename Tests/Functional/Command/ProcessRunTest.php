<?php

/**
 * This file is part of a Spipu Bundle
 *
 * (c) Laurent Minguet
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spipu\ProcessBundle\Tests\Functional\Command;

use Spipu\ConfigurationBundle\Service\ConfigurationManager;
use Spipu\CoreBundle\Tests\EntityManagerTestCaseTrait;
use Spipu\CoreBundle\Tests\WebTestCase;
use Spipu\ProcessBundle\Command\ProcessRunCommand;
use Spipu\ProcessBundle\Exception\InputException;
use Spipu\ProcessBundle\Exception\ProcessException;

class ProcessRunTest extends WebTestCase
{
    use EntityManagerTestCaseTrait;

    protected function resetDatabase(): void
    {
        $queries = [
            'delete from spipu_process_log',
            'delete from spipu_process_task',
        ];

        /** @var \PDO $pdo */
        $pdo = $this->getEntityManager()->getConnection()->getNativeConnection();
        foreach ($queries as $query) {
            $statement = $pdo->query($query);
            if ($statement === false) {
                throw new \Exception(implode(' - ', $pdo->errorInfo()));
            }
            $statement->execute();
        }
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->resetDatabase();
    }

    public function testExecuteMissingProcess()
    {
        $commandTester = self::loadCommand(ProcessRunCommand::class, 'spipu:process:run');
        $foundException = null;
        try {
            $commandTester->execute([]);
        } catch (\Throwable $e) {
            $foundException = $e;
        }
        $this->assertNotNull($foundException);
        $this->assertStringContainsString('Not enough arguments (missing: "process")', $foundException->getMessage());

        $output = trim($commandTester->getDisplay());
        $this->assertStringContainsString("Available process", $output);
        $this->assertStringContainsString("test_hello", $output);
        $this->assertStringContainsString("test_input", $output);
        $this->assertStringContainsString("test_rest", $output);
        $this->assertStringContainsString("test_sleep", $output);
        $this->assertStringContainsString("this_is_a_process_with_a_very_long_name_for_tests", $output);
    }

    public function testExecuteBadProcess()
    {
        $commandTester = self::loadCommand(ProcessRunCommand::class, 'spipu:process:run');
        $foundException = null;
        try {
            $commandTester->execute(['process' => 'foo']);
        } catch (\Throwable $e) {
            $foundException = $e;
        }
        $this->assertNotNull($foundException);
        $this->assertStringContainsString('The asked process does not exists', $foundException->getMessage());

        $output = trim($commandTester->getDisplay());
        $this->assertStringContainsString("Execute process: foo", $output);
    }

    public function testExecuteDisable()
    {
        $configurationManager = self::getContainer()->get(ConfigurationManager::class);
        $configurationManager->set('process.task.can_execute', 0);
        $configurationManager->clearCache();

        $this->expectException(ProcessException::class);
        $this->expectExceptionMessage('Execution is disabled in module configuration');

        try {
            $commandTester = self::loadCommand(ProcessRunCommand::class, 'spipu:process:run');
            $commandTester->execute(['process' => 'foo']);
        } finally {
            $configurationManager = self::getContainer()->get(ConfigurationManager::class);
            $configurationManager->set('process.task.can_execute', 1);
            $configurationManager->clearCache();
        }
    }

    public function testExecuteWithoutDebug()
    {
        $commandTester = self::loadCommand(ProcessRunCommand::class, 'spipu:process:run');
        $commandTester->execute(['process' => 'test_hello']);

        $output = trim($commandTester->getDisplay());
        $this->assertStringContainsString("Execute process: test_hello", $output);
        $this->assertStringNotContainsString("[info___] Process Started [test_hello]", $output);
        $this->assertStringNotContainsString("[info___] Step [hello_world]", $output);
        $this->assertStringNotContainsString("[info___] Process Finished [test_hello]", $output);
        $this->assertStringContainsString("=> Result", $output);
        $this->assertStringContainsString("Hello World Bar from Foo", $output);
    }

    public function testExecuteWithDebug()
    {
        $commandTester = self::loadCommand(ProcessRunCommand::class, 'spipu:process:run');
        $commandTester->execute(['process' => 'test_hello', '--debug' => 1]);

        $output = trim($commandTester->getDisplay());
        $this->assertStringContainsString("Execute process: test_hello", $output);
        $this->assertStringContainsString("[info___] Process Started [test_hello]", $output);
        $this->assertStringContainsString("[info___] Step [hello_world]", $output);
        $this->assertStringContainsString("[info___] Process Finished [test_hello]", $output);
        $this->assertStringContainsString("=> Result", $output);
        $this->assertStringContainsString("Hello World Bar from Foo", $output);
    }

    public function testExecuteWithInputBadFormat()
    {
        $commandTester = self::loadCommand(ProcessRunCommand::class, 'spipu:process:run');

        $foundException = null;
        try {
            $commandTester->execute(['process' => 'test_simple', '--inputs' => ['name_from=foo']]);
        } catch (\Throwable $e) {
            $foundException = $e;
        }
        $this->assertNotNull($foundException);
        $this->assertStringContainsString('The inputs format is invalid. It must be --inputs key:value', $foundException->getMessage());
    }

    public function testExecuteWithInputMissing()
    {
        $commandTester = self::loadCommand(ProcessRunCommand::class, 'spipu:process:run');

        $foundException = null;
        try {
            $commandTester->execute(['process' => 'test_simple', '--inputs' => ['name_from:foo']]);
        } catch (\Throwable $e) {
            $foundException = $e;
        }
        $this->assertNotNull($foundException);

        $output = trim($commandTester->getDisplay());
        $this->assertStringContainsString('This process needs some inputs', $output);
        $this->assertStringContainsString('name_to (string) required', $output);
    }

    public function testExecuteWithInputOk()
    {
        $commandTester = self::loadCommand(ProcessRunCommand::class, 'spipu:process:run');

        $commandTester->execute(['process' => 'test_simple', '--inputs' => ['name_from:Foo', 'name_to:Bar']]);
        $output = trim($commandTester->getDisplay());
        $this->assertStringContainsString('Execute process: test_simple', $output);
        $this->assertStringContainsString('Hello World Bar from Foo', $output);
    }

    public function testExecuteWithAllInputsBadArray()
    {
        $commandTester = self::loadCommand(ProcessRunCommand::class, 'spipu:process:run');

        $this->expectException(InputException::class);
        $this->expectExceptionMessage('json format must be used for array type');

        $commandTester->execute(
            [
                'process' => 'test_input',
                '--debug' => 1,
                '--inputs' => [
                    'firstname:Foo',
                    'lastname:Bar',
                    'current_user_name:admin',
                    'current_user_email:admin@admin.fr',
                    'test_bool:y',
                    'test_int:2',
                    'test_float:3.4',
                    'test_array:[5,6]',
                    'test_optional_string:',
                    'test_option:created',
                    'test_optional_option:',
                    'test_multi_option:created,finished',
                    'test_optional_multi_option:',
                    'test_file:',
                ]
            ]
        );
    }


    public function testExecuteWithAllInputsBadFile()
    {
        $commandTester = self::loadCommand(ProcessRunCommand::class, 'spipu:process:run');

        $this->expectException(InputException::class);
        $this->expectExceptionMessage('This is not a existing or readable file');

        $commandTester->execute(
            [
                'process' => 'test_input',
                '--debug' => 1,
                '--inputs' => [
                    'firstname:Foo',
                    'lastname:Bar',
                    'current_user_name:admin',
                    'current_user_email:admin@admin.fr',
                    'test_bool:y',
                    'test_int:2',
                    'test_float:3.4',
                    'test_array:[5,6]',
                    'test_optional_string:',
                    'test_option:created',
                    'test_optional_option:',
                    'test_multi_option:' . json_encode(['created','finished']),
                    'test_optional_multi_option:',
                    'test_file:bad_file.csv',
                ]
            ]
        );
    }

    public function testExecuteWithAllInputsOk()
    {
        $commandTester = self::loadCommand(ProcessRunCommand::class, 'spipu:process:run');

        $commandTester->execute(
            [
                'process' => 'test_input',
                '--debug' => 1,
                '--inputs' => [
                    'firstname:Foo',
                    'lastname:Bar',
                    'current_user_name:admin',
                    'current_user_email:admin@admin.fr',
                    'test_bool:y',
                    'test_int:2',
                    'test_float:3.4',
                    'test_array:[5,6]',
                    'test_optional_string:',
                    'test_option:created',
                    'test_optional_option:',
                    'test_multi_option:' . json_encode(['created','finished']),
                    'test_optional_multi_option:',
                    'test_file:' . __FILE__,
                ]
            ]
        );

        $output = trim($commandTester->getDisplay());
        $this->assertStringContainsString('Execute process: test_input', $output);
        $this->assertStringContainsString('[info___] Process Started [test_input]', $output);
        $this->assertStringContainsString('[debug__] Input [firstname] (string): Foo', $output);
        $this->assertStringContainsString('[debug__] Input [lastname] (string): Bar', $output);
        $this->assertStringContainsString('[debug__] Input [current_user_name] (string): admin', $output);
        $this->assertStringContainsString('[debug__] Input [current_user_email] (string): admin@admin.fr', $output);
        $this->assertStringContainsString('[debug__] Input [test_int] (int): 2', $output);
        $this->assertStringContainsString('[debug__] Input [test_float] (float): 3.4', $output);
        $this->assertStringContainsString('[debug__] Input [test_bool] (bool): 1', $output);
        $this->assertStringContainsString('[debug__] Input [test_array] (array): [5,6]', $output);
        $this->assertStringContainsString('[debug__] Input [test_optional_string] (string): ', $output);
        $this->assertStringContainsString('[debug__] Input [test_option] (string): created', $output);
        $this->assertStringContainsString('[debug__] Input [test_optional_option] (string): ', $output);
        $this->assertStringContainsString('[debug__] Input [test_multi_option] (array): ["created","finished"]', $output);
        $this->assertStringContainsString('[debug__] Input [test_optional_multi_option] (array): []', $output);
        $this->assertStringContainsString('[debug__] Input [test_file] (file): ' . __FILE__, $output);
        $this->assertStringContainsString('Hello World Foo Bar from Foo Bar', $output);
    }
}
