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

namespace Spipu\ProcessBundle\Step\File\RowReader;

class ActionList
{
    /**
     * @var Action\ActionInterface[]
     */
    private array $actions = [];

    public function __construct(iterable $actions)
    {
        foreach ($actions as $action) {
            $this->addAction($action);
        }
    }

    private function addAction(Action\ActionInterface $action): void
    {
        $this->actions[$action->getCode()] = $action;
    }

    public function execute(string $actionCode, ?string $value, array $parameters = []): ?string
    {
        return $this->actions[$actionCode]->execute($value, $parameters);
    }
}
