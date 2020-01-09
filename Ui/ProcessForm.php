<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Ui;

use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\ConfigReader;
use Spipu\UiBundle\Entity\EntityInterface;
use Spipu\UiBundle\Entity\Form\FieldSet;
use Spipu\UiBundle\Entity\Form\Form;
use Spipu\UiBundle\Service\Ui\Definition\EntityDefinitionInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Process Input Form
 */
class ProcessForm implements EntityDefinitionInterface
{
    /**
     * @var Form
     */
    private $definition;

    /**
     * @var ConfigReader
     */
    private $configReader;

    /**
     * @var string
     */
    private $processCode;

    /**
     * ConfigurationForm constructor.
     * @param ConfigReader $configReader
     */
    public function __construct(ConfigReader $configReader)
    {
        $this->configReader = $configReader;
    }

    /**
     * @param string $processCode
     * @return self
     */
    public function setProcessCode(string $processCode): self
    {
        $this->processCode = $processCode;

        return $this;
    }

    /**
     * @return Form
     * @throws ProcessException
     */
    public function getDefinition(): Form
    {
        if (!$this->definition) {
            $this->prepareForm();
        }

        return $this->definition;
    }

    /**
     * @return void
     * @throws ProcessException
     */
    private function prepareForm(): void
    {
        $fieldSet = new FieldSet('configuration', 'spipu.process.field.process.inputs', 10);
        $fieldSet->setCssClass('col-12');

        $this->definition = new Form('process');
        $this->definition->addFieldSet($fieldSet);

        $definition = $this->getProcessDefinition();
    }

    /**
     * @return array
     * @throws ProcessException
     */
    public function getProcessDefinition(): array
    {
        return $this->configReader->getProcessDefinition($this->processCode);
    }

    /**
     * @param FormInterface $form
     * @param EntityInterface|null $resource
     * @return void
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function setSpecificFields(FormInterface $form, EntityInterface $resource = null): void
    {
    }
}
