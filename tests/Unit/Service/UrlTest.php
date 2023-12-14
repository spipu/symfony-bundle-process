<?php
namespace Spipu\ProcessBundle\Tests\Unit\Service;

use PHPUnit\Framework\TestCase;
use Spipu\ConfigurationBundle\Tests\SpipuConfigurationMock;
use Spipu\CoreBundle\Tests\SymfonyMock;
use Spipu\ProcessBundle\Service\Url;

class UrlTest extends TestCase
{
    /**
     * @param TestCase $testCase
     * @return Url
     */
    public static function getService(TestCase $testCase)
    {
        $service = new Url(
            SymfonyMock::getRouter($testCase),
            SpipuConfigurationMock::getManager($testCase, null, ['app.website.url' => 'http://mock.fr'])
        );

        return $service;
    }

    public function testService()
    {
        $service = self::getService($this);

        $this->assertSame('http://mock.fr/spipu_process_admin_log_show/?id=42', $service->getLogUrl(42));
        $this->assertSame('http://mock.fr/spipu_process_admin_task_show/?id=42', $service->getTaskUrl(42));
    }
}
