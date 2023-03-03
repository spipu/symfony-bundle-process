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

namespace Spipu\ProcessBundle\Controller;

use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Spipu\ProcessBundle\Entity\Process\Input;
use Spipu\ProcessBundle\Entity\Process\Process;
use Spipu\ProcessBundle\Service\TaskManager;
use Spipu\ProcessBundle\Ui\ProcessForm;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\ConfigReader;
use Spipu\UiBundle\Entity\Form\Field;
use Spipu\UiBundle\Exception\UiException;
use Spipu\UiBundle\Form\Options\YesNo;
use Spipu\CoreBundle\Service\AsynchronousCommand;
use Spipu\UiBundle\Service\Ui\FormFactory;
use Spipu\UiBundle\Service\Ui\FormManagerInterface;
use Spipu\UiBundle\Service\Ui\Grid\DataProvider\Doctrine;
use Spipu\UiBundle\Service\Ui\GridFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Spipu\ProcessBundle\Entity\Task;
use Spipu\ProcessBundle\Service\ModuleConfiguration;
use Spipu\ProcessBundle\Service\Manager as ProcessManager;
use Spipu\ProcessBundle\Service\Status;
use Spipu\ProcessBundle\Ui\LogGrid;
use Spipu\ProcessBundle\Ui\TaskGrid;
use Spipu\UserBundle\Entity\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

/**
 * @Route("/process/task")
 * @SuppressWarnings(PMD.CouplingBetweenObjects)
 */
class TaskController extends AbstractController
{
    /**
     * @var ModuleConfiguration
     */
    private $configuration;

    /**
     * @var Status
     */
    private $status;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * TaskController constructor.
     * @param ModuleConfiguration $configuration
     * @param Status $status
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        ModuleConfiguration $configuration,
        Status $status,
        EntityManagerInterface $entityManager
    ) {
        $this->configuration = $configuration;
        $this->status = $status;
        $this->entityManager = $entityManager;
    }

    /**
     * @Route(
     *     "/",
     *     name="spipu_process_admin_task_list",
     *     methods="GET"
     * )
     * @Security("is_granted('ROLE_ADMIN_MANAGE_PROCESS_SHOW')")
     * @param GridFactory $gridFactory
     * @param TaskGrid $taskGrid
     * @return Response
     * @throws UiException
     */
    public function index(GridFactory $gridFactory, TaskGrid $taskGrid): Response
    {
        $this->checkConfiguration();

        $manager = $gridFactory->create($taskGrid);
        $manager->setRoute('spipu_process_admin_task_list');
        $manager->validate();

        return $this->render('@SpipuProcess/task/index.html.twig', ['manager' => $manager]);
    }

    /**
     * @Route(
     *     "/show/{id}",
     *     name="spipu_process_admin_task_show",
     *     methods="GET"
     * )
     * @Security("is_granted('ROLE_ADMIN_MANAGE_PROCESS_SHOW')")
     * @param Task $resource
     * @param YesNo $yesNoOptions
     * @param GridFactory $gridFactory
     * @param LogGrid $logGrid
     * @param ConfigReader $configReader
     * @return Response
     * @throws ProcessException
     * @throws UiException
     */
    public function show(
        Task $resource,
        YesNo $yesNoOptions,
        GridFactory $gridFactory,
        LogGrid $logGrid,
        ConfigReader $configReader
    ): Response {
        $this->checkConfiguration();

        $manager = $gridFactory->create($logGrid);
        $manager->setRoute('spipu_process_admin_task_show', ['id' => $resource->getId()]);

        /** @var Doctrine $dataProvider */
        $dataProvider = $manager->getDataProvider();
        $dataProvider->addCondition('main.task = ' . (int) $resource->getId());

        $manager->validate();

        $processConfig = $configReader->getProcessDefinition($resource->getCode());
        $canKill  = $this->status->canKill($resource->getStatus()) && $this->configuration->hasTaskCanKill();
        $canRerun = $this->status->canRerun($resource->getStatus());

        return $this->render(
            '@SpipuProcess/task/show.html.twig',
            [
                'resource'      => $resource,
                'processConfig' => $processConfig,
                'manager'       => $manager,
                'canKill'       => $canKill,
                'canRerun'      => $canRerun,
                'fieldYesNo'    => new Field('yes_no', ChoiceType::class, 10, ['choices'  => $yesNoOptions]),
            ]
        );
    }

    /**
     * @Route(
     *     "/rerun/{id}",
     *     name="spipu_process_admin_task_rerun",
     *     methods="GET"
     * )
     * @Security("is_granted('ROLE_ADMIN_MANAGE_PROCESS_RERUN')")
     * @param Task $resource
     * @param ProcessManager $processManager
     * @param AsynchronousCommand $asynchronousCommand
     * @return Response
     * @throws Exception
     */
    public function rerun(
        Task $resource,
        ProcessManager $processManager,
        AsynchronousCommand $asynchronousCommand
    ): Response {
        $this->checkConfiguration();

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $redirect = $this->redirectToRoute('spipu_process_admin_task_show', ['id' => $resource->getId()]);

        if (!$this->status->canRerun($resource->getStatus())) {
            $this->addFlashTrans('danger', 'spipu.process.error.rerun');
            return $redirect;
        }

        $process = $processManager->loadFromTask($resource);
        if ($processManager->isProcessLockedByAnotherOne($process)) {
            $this->addFlashTrans('danger', 'spipu.process.error.locked');
            return $redirect;
        }

        $asynchronousCommand->execute('spipu:process:rerun', [$resource->getId()]);
        sleep(1);

        $this->addFlashTrans('success', 'spipu.process.success.rerun');
        return $redirect;
    }

    /**
     * @Route(
     *     "/kill/{id}",
     *     name="spipu_process_admin_task_kill",
     *     methods="GET"
     * )
     * @Security("is_granted('ROLE_ADMIN_MANAGE_PROCESS_KILL')")
     * @param Task $resource
     * @param TaskManager $taskManager
     * @return Response
     */
    public function kill(
        Task $resource,
        TaskManager $taskManager
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        try {
            if (!$this->configuration->hasTaskCanKill()) {
                throw new ProcessException('spipu.process.error.disable');
            }

            $taskManager->kill($resource, 'Killed manually from BO by ' . $this->getUser()->getUserIdentifier());

            $this->addFlashTrans('success', 'spipu.process.success.kill');
        } catch (Throwable $e) {
            $this->addFlashTrans('danger', $e->getMessage());
        }

        return $this->redirectToRoute('spipu_process_admin_task_show', ['id' => $resource->getId()]);
    }

    /**
     * @Route(
     *     "/delete/{id}",
     *     name="spipu_process_admin_task_delete",
     *     methods="DELETE"
     * )
     * @Security("is_granted('ROLE_ADMIN_MANAGE_PROCESS_DELETE')")
     * @param Request $request
     * @param Task $resource
     * @return Response
     */
    public function delete(
        Request $request,
        Task $resource
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (!$this->isCsrfTokenValid('delete_process_task_' . $resource->getId(), $request->request->get('_token'))) {
            $this->addFlashTrans('danger', 'spipu.ui.error.token');
            return $this->redirectToRoute('spipu_process_admin_task_list');
        }

        try {
            $this->entityManager->remove($resource);
            $this->entityManager->flush();

            $this->addFlashTrans('success', 'spipu.ui.success.deleted');
        } catch (Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('spipu_process_admin_task_list');
    }

    /**
     * @Route(
     *     "/execute-choice",
     *     name="spipu_process_admin_task_execute_choice",
     *     methods="GET"
     * )
     * @Security("is_granted('ROLE_ADMIN_MANAGE_PROCESS_EXECUTE')")
     * @param ConfigReader $configReader
     * @return Response
     * @throws ProcessException
     */
    public function executeChoice(ConfigReader $configReader): Response
    {
        $this->checkConfiguration();

        $processes = [];
        foreach (array_keys($configReader->getProcessList()) as $code) {
            $process = $configReader->getProcessDefinition($code);
            if (!$process['options']['can_be_put_in_queue']) {
                continue;
            }

            if ($process['options']['needed_role'] !== null && !$this->isGranted($process['options']['needed_role'])) {
                continue;
            }

            $processes[$process['code']] = [
                'code' => $process['code'],
                'name' => $process['name'],
                'need_inputs' => (count($process['inputs']) > 0),
                'locks' => $process['options']['process_lock'],
                'lock_on_failed' => $process['options']['process_lock_on_failed'],
            ];
        }

        ksort($processes);

        return $this->render(
            '@SpipuProcess/task/execute-choice.html.twig',
            [
                'processes' => $processes,
            ]
        );
    }

    /**
     * @Route(
     *     "/execute/{processCode}",
     *     name="spipu_process_admin_task_execute",
     * )
     * @Security("is_granted('ROLE_ADMIN_MANAGE_PROCESS_EXECUTE')")
     * @param string $processCode
     * @param FormFactory $formFactory
     * @param ProcessForm $processForm
     * @param ProcessManager $processManager
     * @param Request $request
     * @return Response
     * @throws ProcessException
     * @throws UiException
     */
    public function execute(
        string $processCode,
        FormFactory $formFactory,
        ProcessForm $processForm,
        ProcessManager $processManager,
        Request $request
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $processForm->setProcessCode($processCode);
        $currentUser = $this->getUser();
        if ($currentUser instanceof UserInterface) {
            $processForm->setCurrentUserName($currentUser->getUserIdentifier());
            $processForm->setCurrentUserEmail($currentUser->getEmail());
        }

        $processDefinition = $processForm->getProcessDefinition();

        if (
            $processDefinition['options']['needed_role'] !== null
            && !$this->isGranted($processDefinition['options']['needed_role'])
        ) {
            $this->addFlashTrans('danger', 'spipu.process.error.needed_role');
            return $this->redirectToRoute('spipu_process_admin_task_execute_choice');
        }

        $formManager = $formFactory->create($processForm);
        $formManager->setSubmitButton('spipu.process.action.execute', 'play-circle');
        if ($formManager->validate()) {
            try {
                return $this->redirectToRoute(
                    'spipu_process_admin_task_show',
                    [
                        'id' => $this->launchProcess(
                            $processManager,
                            $processCode,
                            $formManager,
                            $processForm->getScheduledAt()
                        )
                    ]
                );
            } catch (Exception $e) {
                $this->container->get('request_stack')->getSession()->getFlashBag()->clear();
                $this->addFlash('danger', $e->getMessage());
            }
        }

        $this->checkConfiguration();
        $this->forceFormParameters($formManager, $request);

        return $this->render(
            '@SpipuProcess/task/execute.html.twig',
            [
                'process'     => $processDefinition,
                'formManager' => $formManager,
            ]
        );
    }

    /**
     * @param ProcessManager $processManager
     * @param string $processCode
     * @param FormManagerInterface $formManager
     * @param DateTimeInterface|null $scheduledAt
     * @return int
     * @throws Exception
     */
    private function launchProcess(
        ProcessManager $processManager,
        string $processCode,
        FormManagerInterface $formManager,
        ?DateTimeInterface $scheduledAt
    ): int {
        $process = $processManager->load($processCode);

        foreach ($process->getInputs()->getInputs() as $input) {
            $inputValue = $formManager->getForm()[$input->getName()]->getData();

            switch ($input->getType()) {
                case 'file':
                    $inputValue = $this->manageInputFile($process, $input, $inputValue);
                    break;

                case 'bool':
                    $inputValue = ($inputValue == 1);
                    break;

                case 'array':
                    if (!is_array($inputValue)) {
                        $inputValue = json_decode($inputValue, true);
                    }
                    break;
            }
            $input->setValue($inputValue);
        }

        if ($scheduledAt) {
            return $processManager->scheduleExecution($process, $scheduledAt);
        }

        $taskId = $processManager->executeAsynchronously($process);

        sleep(1);

        return $taskId;
    }

    /**
     * @param FormManagerInterface $formManager
     * @param Request $request
     * @return void
     */
    private function forceFormParameters(FormManagerInterface $formManager, Request $request): void
    {
        $processParams = $request->query->get('process');
        if (is_array($processParams)) {
            $form = $formManager->getForm();
            foreach ($processParams as $param => $value) {
                if ($form->has($param) && !$form->get($param)->getData()) {
                    $form->get($param)->setData($value);
                }
            }
        }
    }

    /**
     * @param Process $process
     * @param Input $input
     * @param UploadedFile $file
     * @return string
     */
    private function manageInputFile(Process $process, Input $input, UploadedFile $file): string
    {
        $processCode = str_replace(['\\', '/', '.'], '', mb_strtolower($process->getCode()));
        $inputCode = str_replace(['\\', '/', '.'], '', mb_strtolower($input->getName()));

        $folder = $this->configuration->getFolderImport() . '/' . $processCode;
        $folder = str_replace(['\\', '//'], '/', $folder);

        $extension = $file->guessExtension();
        if ($extension === '' || $extension === null) {
            $extension = 'bin';
        }

        $filename = $inputCode . '_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;

        if (!is_dir($folder)) {
            mkdir($folder, 0775, true);
        }
        $file->move($folder, $filename);

        return $folder . '/' . $filename;
    }

    /**
     * @param string $type
     * @param string $message
     * @param array $params
     * @return void
     */
    private function addFlashTrans(string $type, string $message, array $params = []): void
    {
        $this->addFlash($type, $this->trans($message, $params));
    }

    /**
     * @param string $message
     * @param array $params
     * @return string
     */
    private function trans(string $message, array $params = []): string
    {
        return $this->container->get('translator')->trans($message, $params);
    }

    /**
     * @return array
     */
    public static function getSubscribedServices()
    {
        return parent::getSubscribedServices() + [
            'translator',
        ];
    }

    /**
     * @return void
     */
    protected function checkConfiguration(): void
    {
        if (!$this->configuration->hasTaskCanExecute()) {
            $this->addFlashTrans('danger', 'spipu.process.error.execute');
        }
    }
}
