<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Step\File\RowReader\Action;

class ToTitleCase implements ActionInterface
{
    /**
     * @return string
     */
    public function getCode(): string
    {
        return 'toTitleCase';
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
        return mb_convert_case($value, MB_CASE_TITLE);
    }
}
