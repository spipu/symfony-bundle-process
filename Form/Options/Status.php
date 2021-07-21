<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Form\Options;

use Spipu\UiBundle\Form\Options\AbstractOptions;
use Spipu\ProcessBundle\Service\Status as StatusService;

class Status extends AbstractOptions
{
    /**
     * @var StatusService
     */
    private $service;

    /**
     * ProcessLogStatus constructor.
     * @param StatusService $service
     */
    public function __construct(
        StatusService $service
    ) {
        $this->service = $service;
    }

    /**
     * Build the list of the available options
     * @return array
     */
    protected function buildOptions(): array
    {
        $list = [];

        foreach ($this->service->getStatuses() as $code) {
            $list[$code] = 'spipu.process.status.'.$code;
        }

        return $list;
    }
}
