<?php
declare(strict_types=1);

namespace Spipu\ProcessBundle\Step\File\RowReader;

class ActionList
{
    /**
     * @var Action\ActionInterface[]
     */
    private $actions = [];

    /**
     * ActionList constructor.
     * @param iterable $actions
     */
    public function __construct(iterable $actions)
    {
        foreach ($actions as $action) {
            $this->addAction($action);
        }
    }

    /**
     * @param Action\ActionInterface $action
     * @return void
     */
    private function addAction(Action\ActionInterface $action): void
    {
        $this->actions[$action->getCode()] = $action;
    }

    /**
     * @param string $actionCode
     * @param string|null $value
     * @param array $parameters
     * @return null|string
     */
    public function execute(string $actionCode, ?string $value, array $parameters = []): ?string
    {
        return $this->actions[$actionCode]->execute($value, $parameters);
    }
}
