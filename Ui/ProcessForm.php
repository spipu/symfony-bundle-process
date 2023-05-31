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

use DateTime;
use DateTimeInterface;
use Spipu\ProcessBundle\Entity\Process\Input;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\ConfigReader;
use Spipu\ProcessBundle\Service\InputsFactory;
use Spipu\ProcessBundle\Service\ProcessManager;
use Spipu\UiBundle\Entity\EntityInterface;
use Spipu\UiBundle\Entity\Form\Field;
use Spipu\UiBundle\Entity\Form\FieldSet;
use Spipu\UiBundle\Entity\Form\Form;
use Spipu\UiBundle\Exception\FormException;
use Spipu\UiBundle\Form\Options\YesNo;
use Spipu\UiBundle\Service\Ui\Definition\EntityDefinitionInterface;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Process Input Form
 * @SuppressWarnings(PMD.CouplingBetweenObjects)
 * @SuppressWarnings(PMD.ExcessiveClassComplexity)
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
     * @var string|null
     */
    private $currentUserEmail = null;

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
     * @param string|null $currentUserEmail
     * @return self
     */
    public function setCurrentUserEmail(?string $currentUserEmail): self
    {
        $this->currentUserEmail = $currentUserEmail;

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

        if (count($definition['inputs']) > 0) {
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
        }

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
        $field = $this->createFieldBase($input);

        if ($input->getHelp() !== null) {
            $field->addOption('help', $input->getHelp());
        }

        return $field;
    }

    /**
     * @param Input $input
     * @return Field
     * @throws FormException
     */
    private function createFieldBase(Input $input): Field
    {
        if ($input->getOptions()) {
            return $this->createFieldWithOption($input);
        }

        return $this->createFieldWithoutOption($input);
    }

    /**
     * @param Input $input
     * @return Field
     * @throws FormException
     */
    private function createFieldWithOption(Input $input): Field
    {
        if (!in_array($input->getType(), ['string', 'array'])) {
            throw new FormException(
                sprintf(
                    'Unknown input type [%s] with option for field [%s]',
                    $input->getType(),
                    $input->getName()
                )
            );
        }

        return $this->createFieldOptions($input);
    }

    /**
     * @param Input $input
     * @return Field
     * @throws FormException
     */
    private function createFieldWithoutOption(Input $input): Field
    {
        switch ($input->getRealType()) {
            case 'int':
                return $this->createFieldInt($input);

            case 'float':
                return $this->createFieldFloat($input);

            case 'bool':
                return $this->createFieldBool($input);

            case 'array':
                return $this->createFieldArray($input);

            case 'file':
                return $this->createFieldFile($input);

            case 'datetime':
                return $this->createFieldDateTime($input);

            case 'date':
                return $this->createFieldDate($input);

            case 'string':
                return $this->createFieldString($input);
        }

        throw new FormException(
            sprintf(
                'Unknown input type [%s] for field [%s]',
                $input->getRealType(),
                $input->getName()
            )
        );
    }

    /**
     * @param Input $input
     * @return Field
     * @throws FormException
     */
    private function createFieldInt(Input $input): Field
    {
        return $this->createFieldInput($input, Type\IntegerType::class);
    }

    /**
     * @param Input $input
     * @return Field
     * @throws FormException
     */
    private function createFieldFloat(Input $input): Field
    {
        return $this->createFieldInput($input, Type\NumberType::class);
    }

    /**
     * @param Input $input
     * @return Field
     * @throws FormException
     */
    private function createFieldBool(Input $input): Field
    {
        return new Field(
            $input->getName(),
            Type\ChoiceType::class,
            0,
            [
                'label'    => $this->prepareInputLabel($input->getName()),
                'expanded' => false,
                'choices'  => $this->yesNoOptions,
                'required' => $input->isRequired(),
            ]
        );
    }

    /**
     * @param Input $input
     * @return Field
     * @throws FormException
     */
    private function createFieldArray(Input $input): Field
    {
        return new Field(
            $input->getName(),
            Type\TextareaType::class,
            0,
            [
                'label'    => $this->prepareInputLabel($input->getName()),
                'required' => $input->isRequired(),
                'constraints' => [new Json()],
                'help'     => 'spipu.process.help.json'
            ]
        );
    }

    /**
     * @param Input $input
     * @return Field
     * @throws FormException
     */
    private function createFieldDateTime(Input $input): Field
    {
        $input = $this->createFieldInput($input, Type\DateTimeType::class);
        $input
            ->addOption('widget', 'single_text')
            ->addOption('input', 'string')
            ->addOption('with_seconds', true)
        ;

        return $input;
    }

    /**
     * @param Input $input
     * @return Field
     * @throws FormException
     */
    private function createFieldDate(Input $input): Field
    {
        $input = $this->createFieldInput($input, Type\DateType::class);
        $input
            ->addOption('widget', 'single_text')
            ->addOption('input', 'string')
        ;

        return $input;
    }

    /**
     * @param Input $input
     * @return Field
     * @throws FormException
     */
    private function createFieldString(Input $input): Field
    {
        $field = $this->createFieldInput($input, Type\TextType::class);

        if ($input->getName() === 'current_user_name' && $this->currentUserName !== null) {
            $field->setValue($this->currentUserName);
        }

        $emailFields = [ProcessManager::AUTOMATIC_REPORT_EMAIL_FIELD, 'current_user_email'];
        if (in_array($input->getName(), $emailFields) && $this->currentUserEmail !== null) {
            $field->setValue($this->currentUserEmail);
        }

        if ($input->getRegexp()) {
            $field->addOption('constraints', [new Regex($input->getRegexp())]);
            $field->addOption('help', 'Regexp constraint: ' . $input->getRegexp());
        }

        return $field;
    }

    /**
     * @param Input $input
     * @param string $fieldType
     * @return Field
     * @throws FormException
     */
    private function createFieldInput(Input $input, string $fieldType): Field
    {
        return new Field(
            $input->getName(),
            $fieldType,
            0,
            [
                'label'      => $this->prepareInputLabel($input->getName()),
                'required'   => $input->isRequired()
            ]
        );
    }

    /**
     * @param Input $input
     * @return Field
     * @throws FormException
     */
    private function createFieldFile(Input $input): Field
    {
        $field = $this->createFieldInput($input, Type\FileType::class);

        $allowedMimeTypes = $input->getAllowedMimeTypes();
        if (count($allowedMimeTypes) > 0) {
            $field->addOption('constraints', [new File(['mimeTypes' => $allowedMimeTypes])]);
            $field->addOption('help', implode(',', $allowedMimeTypes));
        }

        return $field;
    }

    /**
     * @param Input $input
     * @return Field
     * @throws FormException
     */
    private function createFieldOptions(Input $input): Field
    {
        $options = [
            'label'    => $this->prepareInputLabel($input->getName()),
            'expanded' => false,
            'choices'  => $input->getOptions(),
            'required' => $input->isRequired(),
        ];

        if ($input->getType() === 'array') {
            $options['multiple'] = true;
        }

        return new Field($input->getName(), Type\ChoiceType::class, 0, $options);
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

        /** @var DateTime $date */
        /** @var DateTime $time */

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
