<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Service;

use Spipu\ConfigurationBundle\Service\Manager as ConfigurationManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Url
{
    /**
     * @var UrlGeneratorInterface
     */
    private $router;

    /**
     * @var ConfigurationManager
     */
    private $configurationManager;

    /**
     * Url constructor.
     * @param UrlGeneratorInterface $router
     * @param ConfigurationManager $configurationManager
     */
    public function __construct(
        UrlGeneratorInterface $router,
        ConfigurationManager $configurationManager
    ) {
        $this->router = $router;
        $this->configurationManager = $configurationManager;
    }

    /**
     * Get an process log url
     *
     * @param int $processLogId
     * @return string
     */
    public function getLogUrl(int $processLogId): string
    {
        return
            $this->configurationManager->get('app.website.url').
            $this->router->generate('spipu_process_admin_log_show', ['id' => $processLogId]);
    }

    /**
     * Get an process task url
     *
     * @param int $processTaskId
     * @return string
     */
    public function getTaskUrl(int $processTaskId): string
    {
        return
            $this->configurationManager->get('app.website.url').
            $this->router->generate('spipu_process_admin_task_show', ['id' => $processTaskId]);
    }
}
