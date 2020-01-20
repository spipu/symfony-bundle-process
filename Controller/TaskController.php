<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Controller;

use Exception;
use Spipu\ProcessBundle\Entity\Process\Input;
use Spipu\ProcessBundle\Entity\Process\Process;
use Spipu\ProcessBundle\Ui\ProcessForm;
use Spipu\ProcessBundle\Exception\ProcessException;
use Spipu\ProcessBundle\Service\ConfigReader;
use Spipu\UiBundle\Entity\Form\Field;
use Spipu\UiBundle\Exception\UiException;
use Spipu\UiBundle\Form\Options\YesNo;
use Spipu\CoreBundle\Service\AsynchronousCommand;
use Spipu\UiBundle\Service\Ui\FormFactory;
use Spipu\UiBundle\Service\Ui\Grid\DataProvider\Doctrine;
use Spipu\UiBundle\Service\Ui\GridFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
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
     * TaskController constructor.
     * @param ModuleConfiguration $configuration
     * @param Status $status
     */
    public function __construct(
        ModuleConfiguration $configuration,
        Status $status
    ) {
        $this->configuration = $configuration;
        $this->status = $status;
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
     * @return Response
     * @throws UiException
     */
    public function show(
        Task $resource,
        YesNo $yesNoOptions,
        GridFactory $gridFactory,
        LogGrid $logGrid
    ): Response {
        $manager = $gridFactory->create($logGrid);
        $manager->setRoute('spipu_process_admin_task_show', ['id' => $resource->getId()]);

        /** @var Doctrine $dataProvider */
        $dataProvider = $manager->getDataProvider();
        $dataProvider->addCondition('main.task = '.(int) $resource->getId());

        $manager->validate();

        return $this->render(
            '@SpipuProcess/task/show.html.twig',
            [
                'resource' => $resource,
                'manager'  => $manager,
                'canKill'  => $this->status->canKill($resource->getStatus()) && $this->configuration->hasTaskCanKill(),
                'canRerun' => $this->status->canRerun($resource->getStatus()),
                'fieldYesNo' => new Field('yes_no', ChoiceType::class, 10, ['choices'  => $yesNoOptions]),
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
     * @param AsynchronousCommand $asynchronousCommand
     * @return Response
     */
    public function rerun(
        Task $resource,
        AsynchronousCommand $asynchronousCommand
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $redirect = $this->redirectToRoute('spipu_process_admin_task_show', ['id' => $resource->getId()]);

        if (!$this->status->canRerun($resource->getStatus())) {
            $this->addFlashTrans('danger', 'spipu.process.error.rerun');
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
     * @return Response
     */
    public function kill(
        Task $resource
    ): Response {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $redirect = $this->redirectToRoute('spipu_process_admin_task_show', ['id' => $resource->getId()]);

        if (!$this->status->canKill($resource->getStatus())) {
            $this->addFlashTrans('danger', 'spipu.process.error.kill');
            return $redirect;
        }

        if (!$this->configuration->hasTaskCanKill()) {
            $this->addFlashTrans('danger', 'spipu.process.error.disable');
            return $redirect;
        }

        $resource
            ->setStatus($this->status::FAILED)
            ->incrementTry('Killed manually from BO', false);

        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($resource);
        $entityManager->flush();

        $this->addFlashTrans('success', 'spipu.process.success.kill');
        return $redirect;
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
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($resource);
            $entityManager->flush();

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
    public function executeChoice(ConfigReader $configReader)
    {
        $processes = [];
        foreach (array_keys($configReader->getProcessList()) as $code) {
            $process = $configReader->getProcessDefinition($code);
            if ($process['options']['can_be_put_in_queue']) {
                $processes[$process['code']] = [
                    'code' => $process['code'],
                    'name' => $process['name'],
                    'need_inputs' => (count($process['inputs']) > 0),
                ];
            }
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
     * @return Response
     * @throws ProcessException
     * @throws UiException
     */
    public function execute(
        string $processCode,
        FormFactory $formFactory,
        ProcessForm $processForm,
        ProcessManager $processManager
    ) {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        $processForm->setProcessCode($processCode);
        if ($this->getUser()) {
            $processForm->setCurrentUserName((string) $this->getUser()->getUsername());
        }

        $processDefinition = $processForm->getProcessDefinition();

        $formManager = $formFactory->create($processForm);
        $formManager->setSubmitButton('spipu.process.action.execute', 'play-circle');
        if ($formManager->validate()) {
            try {
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
                            $inputValue = json_decode($inputValue, true);
                            break;
                    }
                    $input->setValue($inputValue);
                }

                $taskId = $processManager->executeAsynchronously($process);
                sleep(1);
                return $this->redirectToRoute('spipu_process_admin_task_show', ['id' => $taskId]);
            } catch (\Exception $e) {
                $this->container->get('session')->getFlashBag()->clear();
                $this->addFlash('danger', $e->getMessage());
            }
        }

        return $this->render(
            '@SpipuProcess/task/execute.html.twig',
            [
                'process'     => $processDefinition,
                'formManager' => $formManager,
            ]
        );
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
}
