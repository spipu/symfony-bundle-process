<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Ui;

use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\ConfigReader;
use Spipu\UiBundle\Entity\EntityInterface;
use Spipu\UiBundle\Entity\Form\Field;
use Spipu\UiBundle\Entity\Form\FieldSet;
use Spipu\UiBundle\Entity\Form\Form;
use Spipu\UiBundle\Exception\FormException;
use Spipu\UiBundle\Form\Options\YesNo;
use Spipu\UiBundle\Service\Ui\Definition\EntityDefinitionInterface;
use Symfony\Component\Form\Extension\Core\Type;
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
     * @var YesNo
     */
    private $yesNoOptions;

    /**
     * @var string
     */
    private $processCode;

    /**
     * ConfigurationForm constructor.
     * @param ConfigReader $configReader
     * @param YesNo $yesNoOptions
     */
    public function __construct(
        ConfigReader $configReader,
        YesNo $yesNoOptions
    ) {
        $this->configReader = $configReader;
        $this->yesNoOptions = $yesNoOptions;
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
     * @throws FormException
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
     * @throws FormException
     */
    private function prepareForm(): void
    {
        $this->definition = new Form('process');

        $definition = $this->getProcessDefinition();

        if (count($definition['inputs']) === 0) {
            return;
        }

        $fieldSet = new FieldSet('configuration', 'spipu.process.field.process.inputs', 10);
        $fieldSet->setCssClass('col-12');

        $position = 0;
        foreach ($definition['inputs'] as $code => $type) {
            $position+= 10;
            $field = $this->createField($code, $type);
            $field->setPosition($position);
            $fieldSet->addField($field);
        }

        $this->definition->addFieldSet($fieldSet);
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

    /**
     * @param string $code
     * @param string $type
     * @return Field
     * @throws FormException
     */
    private function createField(string $code, string $type): Field
    {
        switch ($type) {
            case 'int':
                return $this->createFieldInt($code);

            case 'float':
                return $this->createFieldFloat($code);

            case 'bool':
                return $this->createFieldBool($code);

            case 'array':
                return $this->createFieldArray($code);

            case 'string':
                return $this->createFieldString($code);

            default:
                throw new FormException('Unknown input type');
        }
    }


    /**
     * @param string $code
     * @return Field
     * @throws FormException
     */
    private function createFieldInt(string $code): Field
    {
        return $this->createFieldInput($code, Type\IntegerType::class);
    }

    /**
     * @param string $code
     * @return Field
     * @throws FormException
     */
    private function createFieldFloat(string $code): Field
    {
        return $this->createFieldInput($code, Type\NumberType::class);
    }

    /**
     * @param string $code
     * @return Field
     * @throws FormException
     */
    private function createFieldBool(string $code): Field
    {
        return  new Field(
            $code,
            Type\ChoiceType::class,
            0,
            [
                'label'    => $code,
                'expanded' => false,
                'choices'  => $this->yesNoOptions,
                'required' => true,
            ]
        );
    }

    /**
     * @param string $code
     * @return Field
     * @throws FormException
     */
    private function createFieldArray(string $code): Field
    {
        return  new Field(
            $code,
            Type\TextareaType::class,
            0,
            [
                'label'    => $code,
                'required' => true,
                'help'     => 'spipu.process.help.json'
            ]
        );
    }

    /**
     * @param string $code
     * @return Field
     * @throws FormException
     */
    private function createFieldString(string $code): Field
    {
        return $this->createFieldInput($code, Type\TextType::class);
    }


    /**
     * @param string $code
     * @param string $fieldType
     * @return Field
     * @throws FormException
     */
    private function createFieldInput(string $code, string $fieldType): Field
    {
        return new Field(
            $code,
            $fieldType,
            0,
            [
                'label'    => $code,
                'required' => true,
            ]
        );
    }
}
