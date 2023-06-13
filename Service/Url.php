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

namespace Spipu\ProcessBundle\Service;

use Spipu\ConfigurationBundle\Service\ConfigurationManager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class Url
{
    private UrlGeneratorInterface $router;
    private ConfigurationManager $configurationManager;

    public function __construct(
        UrlGeneratorInterface $router,
        ConfigurationManager $configurationManager
    ) {
        $this->router = $router;
        $this->configurationManager = $configurationManager;
    }

    public function getLogUrl(int $processLogId): string
    {
        return
            $this->configurationManager->get('app.website.url') .
            $this->router->generate('spipu_process_admin_log_show', ['id' => $processLogId]);
    }

    public function getTaskUrl(int $processTaskId): string
    {
        return
            $this->configurationManager->get('app.website.url') .
            $this->router->generate('spipu_process_admin_task_show', ['id' => $processTaskId]);
    }
}
