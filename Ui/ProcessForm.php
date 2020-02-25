<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Ui;

use DateTimeInterface;
use Spipu\ProcessBundle\Entity\Process\Input;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\ConfigReader;
use Spipu\ProcessBundle\Service\InputsFactory;
use Spipu\UiBundle\Entity\EntityInterface;
use Spipu\UiBundle\Entity\Form\Field;
use Spipu\UiBundle\Entity\Form\FieldSet;
use Spipu\UiBundle\Entity\Form\Form;
use Spipu\UiBundle\Exception\FormException;
use Spipu\UiBundle\Form\Options\AbstractOptions;
use Spipu\UiBundle\Form\Options\YesNo;
use Spipu\UiBundle\Service\Ui\Definition\EntityDefinitionInterface;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Process Input Form
 * @SuppressWarnings(PMD.CouplingBetweenObjects)
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
     * @var InputsFactory
     */
    private $inputsFactory;

    /**
     * @var YesNo
     */
    private $yesNoOptions;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var string
     */
    private $processCode;

    /**
     * @var string|null
     */
    private $currentUserName = null;

    /**
     * @var DateTimeInterface|null;
     */
    private $scheduledAt = null;

    /**
     * ConfigurationForm constructor.
     * @param ConfigReader $configReader
     * @param InputsFactory $inputsFactory
     * @param YesNo $yesNoOptions
     * @param TranslatorInterface $translator
     */
    public function __construct(
        ConfigReader $configReader,
        InputsFactory $inputsFactory,
        YesNo $yesNoOptions,
        TranslatorInterface $translator
    ) {
        $this->configReader = $configReader;
        $this->inputsFactory = $inputsFactory;
        $this->yesNoOptions = $yesNoOptions;
        $this->translator = $translator;
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
     * @param string|null $currentUserName
     * @return self
     */
    public function setCurrentUserName(?string $currentUserName): self
    {
        $this->currentUserName = $currentUserName;

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
        $inputs = $this->inputsFactory->create($definition['inputs']);

        $fieldSet = new FieldSet('configuration', 'spipu.process.fieldset.inputs', 10);
        $fieldSet->setCssClass('col-12 col-md-6');
        $position = 0;
        foreach ($inputs->getInputs() as $input) {
            $position += 10;
            $field = $this->createField($input);
            $field->setPosition($position);
            $fieldSet->addField($field);
        }
        $this->definition->addFieldSet($fieldSet);

        $this->definition
            ->addFieldSet(
                (new FieldSet('execution', 'spipu.process.field.task.scheduled_at', 20))
                    ->setCssClass('col-12 col-md-6')
                    ->addField(new Field(
                        'taskExecutedAtDate',
                        Type\DateType::class,
                        10,
                        [
                            'label'    => 'spipu.process.field.task.scheduled_at_date',
                            'required' => false,
                            'trim'     => true,
                            'widget' => 'single_text',
                        ]
                    ))
                    ->addField(new Field(
                        'taskExecutedAtTime',
                        Type\TimeType::class,
                        20,
                        [
                            'label'    => 'spipu.process.field.task.scheduled_at_time',
                            'required' => false,
                            'trim'     => true,
                            'widget' => 'single_text',
                        ]
                    ))
            );
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
     * @param Input $input
     * @return Field
     * @throws FormException
     */
    private function createField(Input $input): Field
    {
        $code = $input->getName();

        if ($input->getOptions()) {
            return $this->createFieldOptions($code, $input->getOptions());
        }

        switch ($input->getType()) {
            case 'int':
                return $this->createFieldInt($code);

            case 'float':
                return $this->createFieldFloat($code);

            case 'bool':
                return $this->createFieldBool($code);

            case 'array':
                return $this->createFieldArray($code);

            case 'file':
                return $this->createFieldFile($code, $input->getAllowedMimeTypes());

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
        return new Field(
            $code,
            Type\ChoiceType::class,
            0,
            [
                'label'    => $this->prepareInputLabel($code),
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
        return new Field(
            $code,
            Type\TextareaType::class,
            0,
            [
                'label'    => $this->prepareInputLabel($code),
                'required' => true,
                'constraints' => [new Json()],
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
        $field = $this->createFieldInput($code, Type\TextType::class);

        if ($code === 'current_user_name' && $this->currentUserName !== null) {
            $field->setValue($this->currentUserName);
        }

        return $field;
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
                'label'      => $this->prepareInputLabel($code),
                'required'   => true
            ]
        );
    }

    /**
     * @param string $code
     * @param array $allowedMimeTypes
     * @return Field
     * @throws FormException
     */
    private function createFieldFile(string $code, array $allowedMimeTypes): Field
    {
        $field = $this->createFieldInput($code, Type\FileType::class);

        if (count($allowedMimeTypes) > 0) {
            $field->addOption('constraints', [new File(['mimeTypes' => $allowedMimeTypes])]);
            $field->addOption('help', implode(',', $allowedMimeTypes));
        }

        return $field;
    }
    
    /**
     * @param string $code
     * @param AbstractOptions $options
     * @return Field
     * @throws FormException
     */
    private function createFieldOptions(string $code, AbstractOptions $options): Field
    {
        return new Field(
            $code,
            Type\ChoiceType::class,
            0,
            [
                'label'    => $this->prepareInputLabel($code),
                'expanded' => false,
                'choices'  => $options,
                'required' => true,
            ]
        );
    }

    /**
     * @param string $code
     * @return string
     */
    private function prepareInputLabel(string $code): string
    {
        return ucwords(str_replace('_', ' ', $code));
    }

    /**
     * @param FormInterface $form
     * @param EntityInterface|null $resource
     * @return void
     * @throws FormException
     * @SuppressWarnings(PMD.UnusedFormalParameter)
     */
    public function setSpecificFields(FormInterface $form, EntityInterface $resource = null): void
    {
        $date = $form['taskExecutedAtDate']->getData();
        $time = $form['taskExecutedAtTime']->getData();

        if ($date && !$time) {
            $form['taskExecutedAtTime']->addError(
                new FormError($this->translator->trans('spipu.process.error.required'))
            );
            throw new FormException('spipu.process.error.generic');
        }

        if ($time && !$date) {
            $form['taskExecutedAtDate']->addError(
                new FormError($this->translator->trans('spipu.process.error.required'))
            );
            throw new FormException('spipu.process.error.generic');
        }

        if (!$time && !$date) {
            $this->scheduledAt = null;
            return;
        }

        /** @var \DateTime $date */
        /** @var \DateTime $time */

        $this->scheduledAt = $date->setTime((int) $time->format('H'), (int) $time->format('i'));
    }

    /**
     * @return DateTimeInterface|null
     */
    public function getScheduledAt(): ?DateTimeInterface
    {
        return $this->scheduledAt;
    }
}
