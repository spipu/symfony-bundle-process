<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Step\File\RowReader\Action;

class CleanSpace implements ActionInterface
{
    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'cleanSpace';
    }

    /**
     * Execute the action
     * @param string|null $value
     * @param array $parameters OPTIONAL.
     *
     * @return null|string
     */
    public function execute(?string $value, array $parameters = []): ?string
    {
        return str_replace(" ", "", $value);
    }
}
