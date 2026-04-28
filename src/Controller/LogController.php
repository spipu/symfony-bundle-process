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

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Spipu\UiBundle\Service\Ui\GridFactory;
use Spipu\ProcessBundle\Repository\LogRepository;
use Spipu\ProcessBundle\Ui\LogGrid;
use Spipu\CoreBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/process/log')]
class LogController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    #[Route(path: '/', name: 'spipu_process_admin_log_list', methods: 'GET')]
    #[IsGranted('ROLE_ADMIN_MANAGE_PROCESS_SHOW')]
    public function index(GridFactory $gridFactory, LogGrid $logGrid): Response
    {
        $manager = $gridFactory->create($logGrid);
        $manager->setRoute('spipu_process_admin_log_list');
        if ($manager->validate()) {
            return $this->redirectToRoute('spipu_process_admin_log_list');
        }

        return $this->render('@SpipuProcess/log/index.html.twig', ['manager' => $manager]);
    }

    #[Route(path: '/show/{id}', name: 'spipu_process_admin_log_show', methods: 'GET')]
    #[IsGranted('ROLE_ADMIN_MANAGE_PROCESS_SHOW')]
    public function show(LogRepository $logRepository, int $id): Response
    {
        $resource = $logRepository->findOneBy(['id' => $id]);
        if (!$resource) {
            throw $this->createNotFoundException();
        }

        $messages = json_decode($resource->getContent(), true);

        $start = (isset($messages[0]['date']) ? $messages[0]['date'] : 0);

        $duration = 0;
        $formatedDuration = null;
        $logGraph = [];
        foreach ($messages as &$message) {
            $duration = (int) $message['date'] - $start;
            $formatedDuration = gmdate('H:i:s', $duration);

            $logGraph[] = [
                't' => $duration,
                'd' => $formatedDuration,
                'v' => [
                    'mem'  => $this->convertMemoryValue($message['memory']),
                    'real' => $this->convertMemoryValue($message['memory_real'] ?? $message['memory_peak']),
                ],
            ];

            $message['duration'] = $formatedDuration;
            $message['class']    = $this->getCssFromStatus($message['level']);
            $message['message']  = htmlentities($message['message']);
            $message['message']  = preg_replace(
                '/\[(http[s]?:\/\/[^\]]+)\]/',
                '<a href="$1">$1</a>',
                $message['message']
            );
            $message['message']  = str_replace(['[',']'], ['<b>[',']</b>'], $message['message']);
        }

        if ($duration === 0 || !in_array($resource->getStatus(), ['failed', 'finished'])) {
            $formatedDuration = null;
            $logGraph = null;
        }

        return $this->render(
            '@SpipuProcess/log/show.html.twig',
            [
                'resource' => $resource,
                'messages' => $messages,
                'duration' => $formatedDuration,
                'logGraph' => $logGraph,
            ]
        );
    }

    private function convertMemoryValue(int $value): float
    {
        return ((float) $value) / (1024. * 1024.);
    }

    #[Route(path: '/delete/{id}', name: 'spipu_process_admin_log_delete', methods: 'DELETE')]
    #[IsGranted('ROLE_ADMIN_MANAGE_PROCESS_DELETE')]
    public function delete(
        Request $request,
        LogRepository $logRepository,
        int $id
    ): Response {
        $resource = $logRepository->findOneBy(['id' => $id]);
        if (!$resource) {
            throw $this->createNotFoundException();
        }

        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        if (!$this->isCsrfTokenValid('delete_process_log_' . $resource->getId(), $request->request->get('_token'))) {
            $this->addFlashTrans('danger', 'spipu.ui.error.token');

            return $this->redirectToRoute('spipu_process_admin_log_list');
        }

        try {
            $this->entityManager->remove($resource);
            $this->entityManager->flush();

            $this->addFlashTrans('success', 'spipu.ui.success.deleted');
        } catch (Exception $e) {
            $this->addFlash('danger', $e->getMessage());
        }

        return $this->redirectToRoute('spipu_process_admin_log_list');
    }

    private function getCssFromStatus(string $status): string
    {
        $levelAlertsLink = [
            'emergency' => 'table-danger',
            'alert'     => 'table-danger',
            'critical'  => 'table-danger',
            'error'     => 'table-danger',
            'warning'   => 'table-warning',
            'notice'    => 'table-success',
            'info'      => 'table-primary',
            'debug'     => 'table-secondary',
        ];

        if (!array_key_exists($status, $levelAlertsLink)) {
            return '';
        }

        return $levelAlertsLink[$status];
    }
}
