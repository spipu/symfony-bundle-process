<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Ui;

use Spipu\UiBundle\Exception\GridException;
use Spipu\UiBundle\Service\Ui\Definition\GridDefinitionInterface;
use Spipu\UiBundle\Entity\Grid;
use Spipu\UiBundle\Form\Options\YesNo as OptionsYesNo;
use Spipu\ProcessBundle\Form\Options\Process as OptionsProcess;
use Spipu\ProcessBundle\Form\Options\Status as OptionsStatus;

/**
 * Class TaskGrid
 * @SuppressWarnings(PMD.CouplingBetweenObjects)
 */
class TaskGrid implements GridDefinitionInterface
{
    /**
     * @var Grid\Grid
     */
    private $definition;

    /**
     * @var OptionsProcess
     */
    private $optionsProcess;

    /**
     * @var OptionsStatus
     */
    private $optionsStatus;

    /**
     * @var OptionsYesNo
     */
    private $optionsYesNo;

    /**
     * TaskGrid constructor.
     * @param OptionsProcess $optionsProcess
     * @param OptionsStatus $optionsStatus
     * @param OptionsYesNo $optionsYesNo
     */
    public function __construct(
        OptionsProcess $optionsProcess,
        OptionsStatus $optionsStatus,
        OptionsYesNo $optionsYesNo
    ) {
        $this->optionsProcess = $optionsProcess;
        $this->optionsStatus = $optionsStatus;
        $this->optionsYesNo = $optionsYesNo;
    }

    /**
     * @return Grid\Grid
     * @throws GridException
     */
    public function getDefinition(): Grid\Grid
    {
        if (!$this->definition) {
            $this->prepareGrid();
        }

        return $this->definition;
    }

    /**
     * @return void
     * @throws GridException
     */
    private function prepareGrid(): void
    {
        $this->definition = (new Grid\Grid('process_task', 'SpipuProcessBundle:Task'))
            ->setPager(
                (new Grid\Pager([10, 20, 50, 100], 20))
            )
            ->addColumn(
                (new Grid\Column('id', 'spipu.process.field.task.id', 'id', 10))
                    ->setType((new Grid\ColumnType(Grid\ColumnType::TYPE_INTEGER)))
                    ->setFilter((new Grid\ColumnFilter(true))->useRange())
                    ->useSortable()
            )
            ->addColumn(
                (new Grid\Column('code', 'spipu.process.field.task.code', 'code', 20))
                    ->setType(
                        (new Grid\ColumnType(Grid\ColumnType::TYPE_SELECT))
                            ->setOptions($this->optionsProcess)
                            ->setTranslate(false)
                    )
                    ->setFilter((new Grid\ColumnFilter(true)))
                    ->useSortable()
            )
            ->addColumn(
                (new Grid\Column('status', 'spipu.process.field.task.status', 'status', 30))
                    ->setType(
                        (new Grid\ColumnType(Grid\ColumnType::TYPE_SELECT))
                            ->setOptions($this->optionsStatus)
                            ->setTemplateField('@SpipuProcess/grid/field/status.html.twig')
                    )
                    ->setFilter((new Grid\ColumnFilter(true)))
                    ->useSortable()
            )
            ->addColumn(
                (new Grid\Column('progress', 'spipu.process.field.task.progress', 'progress', 40))
                    ->setType(
                        (new Grid\ColumnType(Grid\ColumnType::TYPE_INTEGER))
                            ->setTemplateField('@SpipuProcess/grid/field/progress.html.twig')
                    )
                    ->setFilter((new Grid\ColumnFilter(true))->useRange())
                    ->useSortable()
            )
            ->addColumn(
                (new Grid\Column('try_last_at', 'spipu.process.field.task.try_last_at', 'tryLastAt', 50))
                    ->setType((new Grid\ColumnType(Grid\ColumnType::TYPE_DATETIME)))
                    ->setFilter((new Grid\ColumnFilter(true))->useRange())
                    ->useSortable()
            )
            ->addColumn(
                (new Grid\Column('try_number', 'spipu.process.field.task.try_number', 'tryNumber', 60))
                    ->setType((new Grid\ColumnType(Grid\ColumnType::TYPE_INTEGER)))
                    ->setFilter((new Grid\ColumnFilter(true))->useRange())
                    ->useSortable()
            )
            ->addColumn(
                (new Grid\Column(
                    'can_be_run_automatically',
                    'spipu.process.field.task.can_be_run_automatically',
                    'canBeRerunAutomatically',
                    70
                ))
                    ->setType(
                        (new Grid\ColumnType(Grid\ColumnType::TYPE_SELECT))
                            ->setOptions($this->optionsYesNo)
                            ->setTemplateField('@SpipuUi/grid/field/yes-no.html.twig')
                    )
                    ->setFilter((new Grid\ColumnFilter(true)))
                    ->useSortable()
            )
            ->addColumn(
                (new Grid\Column('created_at', 'spipu.process.field.task.created_at', 'createdAt', 80))
                    ->setType((new Grid\ColumnType(Grid\ColumnType::TYPE_DATETIME)))
                    ->setFilter((new Grid\ColumnFilter(true))->useRange())
                    ->useSortable()
            )
            ->addColumn(
                (new Grid\Column('updated_at', 'spipu.process.field.task.updated_at', 'updatedAt', 90))
                    ->setType((new Grid\ColumnType(Grid\ColumnType::TYPE_DATETIME)))
                    ->setFilter((new Grid\ColumnFilter(true))->useRange())
                    ->useSortable()
            )
            ->setDefaultSort('id', 'desc')
            ->addGlobalAction(
                (new Grid\Action(
                    'execute',
                    'spipu.process.action.execute-choice',
                    10,
                    'spipu_process_admin_task_execute_choice'
                ))
                    ->setCssClass('success')
                    ->setIcon('play')
            )

            ->addRowAction(
                (new Grid\Action('show', 'spipu.ui.action.show', 10, 'spipu_process_admin_task_show'))
                    ->setCssClass('primary')
                    ->setIcon('eye')
            )
        ;
    }
}
