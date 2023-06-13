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
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Spipu\ProcessBundle\Entity\Process\Input;
use Spipu\ProcessBundle\Entity\Process\Process;
use Spipu\ProcessBundle\Service\TaskManager;
use Spipu\ProcessBundle\Ui\ProcessForm;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\ConfigReader;
use Spipu\UiBundle\Entity\Form\Field;
use Spipu\UiBundle\Form\Options\YesNo;
use Spipu\CoreBundle\Service\AsynchronousCommand;
use Spipu\UiBundle\Service\Ui\FormFactory;
use Spipu\UiBundle\Service\Ui\FormManagerInterface;
use Spipu\UiBundle\Service\Ui\Grid\DataProvider\Doctrine;
use Spipu\UiBundle\Service\Ui\GridFactory;
use Spipu\ProcessBundle\Entity\Task;
use Spipu\ProcessBundle\Service\ModuleConfiguration;
use Spipu\ProcessBundle\Service\Manager as ProcessManager;
use Spipu\ProcessBundle\Service\Status;
use Spipu\ProcessBundle\Ui\LogGrid;
use Spipu\ProcessBundle\Ui\TaskGrid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;
use Throwable;

/**
 * @SuppressWarnings(PMD.CouplingBetweenObjects)
 */
#[Route(path: '/process/task')]
class TaskController extends AbstractController
{
    private ModuleConfiguration $configuration;
    private Status $status;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ModuleConfiguration $configuration,
        Status $status,
        EntityManagerInterface $entityManager
    ) {
        $this->configuration = $configuration;
        $this->status = $status;
        $this->entityManager = $entityManager;
    }

    #[Route(path: '/', name: 'spipu_process_admin_task_list', methods: 'GET')]
    #[IsGranted('ROLE_ADMIN_MANAGE_PROCESS_SHOW')]
    public function index(GridFactory $gridFactory, TaskGrid $taskGrid): Response
    {
        $this->checkConfiguration();

        $manager = $gridFactory->create($taskGrid);
        $manager->setRoute('spipu_process_admin_task_list');
        if ($manager->validate()) {
            return $this->redirectToRoute('spipu_process_admin_task_list');
        }

        return $this->render('@SpipuProcess/task/index.html.twig', ['manager' => $manager]);
    }

    #[Route(path: '/show/{id}', name: 'spipu_process_admin_task_show', methods: 'GET')]
    #[IsGranted('ROLE_ADMIN_MANAGE_PROCESS_SHOW')]
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

        if ($manager->validate()) {
            return $this->redirectToRoute('spipu_process_admin_task_show', ['id' => $resource->getId()]);
        }

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

    #[Route(path: '/rerun/{id}', name: 'spipu_process_admin_task_rerun', methods: 'GET')]
    #[IsGranted('ROLE_ADMIN_MANAGE_PROCESS_RERUN')]
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
        $blockingTaskId = $processManager->getBlockingTaskId($process);
        if ($blockingTaskId !== null) {
            $this->addFlashTrans(
                'danger',
                'spipu.process.error.locked',
                ['%taskId' => $blockingTaskId]
            );
            return $redirect;
        }

        $asynchronousCommand->execute('spipu:process:rerun', [$resource->getId()]);
        sleep(1);

        $this->addFlashTrans('success', 'spipu.process.success.rerun');
        return $redirect;
    }

    #[Route(path: '/kill/{id}', name: 'spipu_process_admin_task_kill', methods: 'GET')]
    #[IsGranted('ROLE_ADMIN_MANAGE_PROCESS_KILL')]
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

    #[Route(path: '/delete/{id}', name: 'spipu_process_admin_task_delete', methods: 'DELETE')]
    #[IsGranted('ROLE_ADMIN_MANAGE_PROCESS_DELETE')]
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

    #[Route(path: '/execute-choice', name: 'spipu_process_admin_task_execute_choice', methods: 'GET')]
    #[IsGranted('ROLE_ADMIN_MANAGE_PROCESS_EXECUTE')]
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

    #[Route(path: '/execute/{processCode}', name: 'spipu_process_admin_task_execute')]
    #[IsGranted('ROLE_ADMIN_MANAGE_PROCESS_EXECUTE')]
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
            if (method_exists($currentUser, 'getEmail')) {
                $processForm->setCurrentUserEmail($currentUser->getEmail());
            }
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

    private function manageInputFile(Process $process, Input $input, ?UploadedFile $file): ?string
    {
        if ($file === null) {
            return null;
        }

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

    private function addFlashTrans(string $type, string $message, array $params = []): void
    {
        $this->addFlash($type, $this->trans($message, $params));
    }

    private function trans(string $message, array $params = []): string
    {
        return $this->container->get('translator')->trans($message, $params);
    }

    public static function getSubscribedServices(): array
    {
        return parent::getSubscribedServices() + [
            'translator',
        ];
    }

    protected function checkConfiguration(): void
    {
        if (!$this->configuration->hasTaskCanExecute()) {
            $this->addFlashTrans('danger', 'spipu.process.error.execute');
        }
    }
}
