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
use Spipu\UiBundle\Exception\GridException;
use Spipu\UiBundle\Exception\UiException;
use Spipu\UiBundle\Service\Ui\GridFactory;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Spipu\ProcessBundle\Entity\Log;
use Spipu\ProcessBundle\Ui\LogGrid;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/process/log")
 */
class LogController extends AbstractController
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        EntityManagerInterface $entityManager
    ) {
        $this->entityManager = $entityManager;
    }

    /**
     * @Route(
     *     "/",
     *     name="spipu_process_admin_log_list",
     *     methods="GET"
     * )
     * @Security("is_granted('ROLE_ADMIN_MANAGE_PROCESS_SHOW')")
     * @param GridFactory $gridFactory
     * @param LogGrid $logGrid
     * @return Response
     * @throws UiException
     * @throws GridException
     */
    public function index(GridFactory $gridFactory, LogGrid $logGrid): Response
    {
        $manager = $gridFactory->create($logGrid);
        $manager->setRoute('spipu_process_admin_log_list');
        $manager->validate();
        if ($manager->needRefresh()) {
            return $this->redirectToRoute('spipu_process_admin_log_list');
        }

        return $this->render('@SpipuProcess/log/index.html.twig', ['manager' => $manager]);
    }

    /**
     * @Route(
     *     "/show/{id}",
     *     name="spipu_process_admin_log_show",
     *     methods="GET"
     * )
     * @Security("is_granted('ROLE_ADMIN_MANAGE_PROCESS_SHOW')")
     * @param Log $resource
     * @return Response
     */
    public function show(Log $resource): Response
    {
        $messages = json_decode($resource->getContent(), true);

        $start = (isset($messages[0]['date']) ? $messages[0]['date'] : 0);
        foreach ($messages as &$message) {
            $duration = (int) $message['date'] - $start;
            $sec = ($duration % 60);
            $min = (int) ($duration / 60);

            $message['duration'] = ($min > 9 ? '' : '0') . ($min) . ':' . ($sec > 9 ? '' : '0') . ($sec);
            $message['class']    = $this->getCssFromStatus($message['level']);
            $message['message']  = htmlentities($message['message']);
            $message['message']  = preg_replace(
                '/\[(http[s]?:\/\/[^\]]+)\]/',
                '<a href="$1">$1</a>',
                $message['message']
            );
            $message['message']  = str_replace(['[',']'], ['<b>[',']</b>'], $message['message']);
        }

        return $this->render(
            '@SpipuProcess/log/show.html.twig',
            [
                'resource' => $resource,
                'messages' => $messages,
            ]
        );
    }

    /**
     * @Route(
     *     "/delete/{id}",
     *     name="spipu_process_admin_log_delete",
     *     methods="DELETE"
     * )
     * @Security("is_granted('ROLE_ADMIN_MANAGE_PROCESS_DELETE')")
     * @param Request $request
     * @param Log $resource
     * @return Response
     */
    public function delete(
        Request $request,
        Log $resource
    ): Response {
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

    /**
     * @param string $status
     * @return string
     */
    private function getCssFromStatus(string $status): string
    {
        $levelAlertsLink = [
            'emergency' => 'alert-danger',
            'alert'     => 'alert-danger',
            'critical'  => 'alert-danger',
            'error'     => 'alert-danger',
            'warning'   => 'alert-warning',
            'notice'    => 'alert-success',
            'info'      => 'alert-primary',
            'debug'     => 'alert-secondary',
        ];

        if (!array_key_exists($status, $levelAlertsLink)) {
            return '';
        }

        return $levelAlertsLink[$status];
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
