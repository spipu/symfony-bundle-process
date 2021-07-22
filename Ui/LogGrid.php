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

namespace Spipu\ProcessBundle\Ui;

use Spipu\UiBundle\Exception\GridException;
use Spipu\UiBundle\Service\Ui\Definition\GridDefinitionInterface;
use Spipu\UiBundle\Entity\Grid;
use Spipu\ProcessBundle\Form\Options\Process as OptionsProcess;
use Spipu\ProcessBundle\Form\Options\Status as OptionsStatus;

/**
 * Class LogGrid
 * @SuppressWarnings(PMD.CouplingBetweenObjects)
 */
class LogGrid implements GridDefinitionInterface
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
     * TaskGrid constructor.
     * @param OptionsProcess $optionsProcess
     * @param OptionsStatus $optionsStatus
     */
    public function __construct(
        OptionsProcess $optionsProcess,
        OptionsStatus $optionsStatus
    ) {
        $this->optionsProcess = $optionsProcess;
        $this->optionsStatus = $optionsStatus;
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
        $this->definition = (new Grid\Grid('process_log', 'SpipuProcessBundle:Log'))
            ->setPager(
                (new Grid\Pager([10, 20, 50, 100], 20))
            )
            ->addColumn(
                (new Grid\Column('id', 'spipu.process.field.log.id', 'id', 10))
                    ->setType((new Grid\ColumnType(Grid\ColumnType::TYPE_INTEGER)))
                    ->setFilter((new Grid\ColumnFilter(true))->useRange())
                    ->useSortable()
            )
            ->addColumn(
                (new Grid\Column('code', 'spipu.process.field.log.code', 'code', 20))
                    ->setType(
                        (new Grid\ColumnType(Grid\ColumnType::TYPE_SELECT))
                            ->setOptions($this->optionsProcess)
                            ->setTranslate(false)
                    )
                    ->setFilter((new Grid\ColumnFilter(true)))
                    ->useSortable()
            )
            ->addColumn(
                (new Grid\Column('status', 'spipu.process.field.log.status', 'status', 30))
                    ->setType(
                        (new Grid\ColumnType(Grid\ColumnType::TYPE_SELECT))
                            ->setOptions($this->optionsStatus)
                            ->setTemplateField('@SpipuProcess/grid/field/status.html.twig')
                    )
                    ->setFilter((new Grid\ColumnFilter(true)))
                    ->useSortable()
            )
            ->addColumn(
                (new Grid\Column('progress', 'spipu.process.field.log.progress', 'progress', 40))
                    ->setType(
                        (new Grid\ColumnType(Grid\ColumnType::TYPE_INTEGER))
                            ->setTemplateField('@SpipuProcess/grid/field/progress.html.twig')
                    )
                    ->setFilter((new Grid\ColumnFilter(true))->useRange())
                    ->useSortable()
            )
            ->addColumn(
                (new Grid\Column('created_at', 'spipu.process.field.log.created_at', 'createdAt', 80))
                    ->setType((new Grid\ColumnType(Grid\ColumnType::TYPE_DATETIME)))
                    ->setFilter((new Grid\ColumnFilter(true))->useRange())
                    ->useSortable()
            )
            ->addColumn(
                (new Grid\Column('updated_at', 'spipu.process.field.log.updated_at', 'updatedAt', 90))
                    ->setType((new Grid\ColumnType(Grid\ColumnType::TYPE_DATETIME)))
                    ->setFilter((new Grid\ColumnFilter(true))->useRange())
                    ->useSortable()
            )
            ->setDefaultSort('id', 'desc')
            ->addRowAction(
                (new Grid\Action('show', 'spipu.ui.action.show', 10, 'spipu_process_admin_log_show'))
                    ->setCssClass('primary')
                    ->setIcon('eye')
            )
        ;
    }
}
