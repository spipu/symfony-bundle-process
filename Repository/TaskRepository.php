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

namespace Spipu\ProcessBundle\Repository;

use DateInterval;
use DateTime;
use DateTimeInterface;
use Spipu\ProcessBundle\Entity\Task;
use Spipu\ProcessBundle\Service\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Task|null find($id, $lockMode = null, $lockVersion = null)
 * @method Task|null findOneBy(array $criteria, array $orderBy = null)
 * @method Task[]    findAll()
 * @method Task[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TaskRepository extends ServiceEntityRepository
{
    /**
     * @var Status
     */
    private $status;

    /**
     * ProcessTaskRepository constructor.
     * @param ManagerRegistry $registry
     * @param Status $status
     */
    public function __construct(
        ManagerRegistry $registry,
        Status $status
    ) {
        parent::__construct($registry, Task::class);
        $this->status = $status;
    }

    /**
     * @param int $failedMaxRetry
     * @return int[]
     */
    public function getIdsToRerunAutomatically(int $failedMaxRetry): array
    {
        $query = $this
            ->createQueryBuilder('t')
            ->select('t.id')
            ->andWhere('t.status in (:statuses)')
            ->andWhere('t.canBeRerunAutomatically = (:executable)')
            ->andWhere('t.tryNumber < (:maxTry)')
            ->setParameter('statuses', $this->status->getExecutableStatuses())
            ->setParameter('maxTry', $failedMaxRetry)
            ->setParameter('executable', true)
            ->getQuery();

        $rows = $query->getArrayResult();

        $list = [];
        foreach ($rows as $row) {
            $list[] = (int) $row['id'];
        }

        return $list;
    }

    /**
     * @return int[]
     */
    public function getScheduledIdsToRun(): array
    {
        $query = $this
            ->createQueryBuilder('t')
            ->select('t.id')
            ->andWhere('t.status = :status')
            ->andWhere('t.scheduledAt is not null')
            ->andWhere('t.scheduledAt <= :currentDate')
            ->setParameter('status', $this->status->getCreatedStatus())
            ->setParameter('currentDate', new DateTime())
            ->getQuery();

        $rows = $query->getArrayResult();

        $list = [];
        foreach ($rows as $row) {
            $list[] = (int) $row['id'];
        }

        return $list;
    }

    /**
     * @param DateTimeInterface $limitDate
     * @return int
     */
    public function deleteFinishedTasks(DateTimeInterface $limitDate): int
    {
        $query = $this
            ->createQueryBuilder('t')
            ->delete()
            ->andWhere('t.status = (:status)')
            ->andWhere('t.updatedAt < (:date)')
            ->setParameter('status', $this->status->getFinishedStatus())
            ->setParameter('date', $limitDate)
            ->getQuery();

        return $query->execute();
    }

    /**
     * @param int $nbMinutes
     * @return int[]
     */
    public function getRunningIdsToCheck(int $nbMinutes): array
    {
        if ($nbMinutes < 1) {
            $nbMinutes = 5;
        }

        $date = new DateTime();
        $date->sub(new DateInterval('PT' . $nbMinutes . 'M'));

        $query = $this
            ->createQueryBuilder('t')
            ->select('t.id')
            ->andWhere('t.status = :status')
            ->andWhere('t.pidValue is not null')
            ->andWhere('t.pidValue > 0')
            ->andWhere('t.pidLastSeen is not null')
            ->andWhere('t.pidLastSeen <= :currentDate')
            ->setParameter('status', $this->status->getRunningStatus())
            ->setParameter('currentDate', $date)
            ->getQuery();

        $rows = $query->getArrayResult();

        $list = [];
        foreach ($rows as $row) {
            $list[] = (int) $row['id'];
        }

        return $list;
    }

    /**
     * @param DateTime $waitingDate
     * @return int[]
     */
    public function getWaitingIdsToRun(DateTime $waitingDate): array
    {
        $query = $this
            ->createQueryBuilder('t')
            ->select('t.id')
            ->andWhere('t.status = :status')
            ->andWhere('t.scheduledAt is null')
            ->andWhere('t.createdAt <= :waitingDate')
            ->setParameter('status', $this->status->getCreatedStatus())
            ->setParameter('waitingDate', $waitingDate)
            ->getQuery();

        $rows = $query->getArrayResult();

        $list = [];
        foreach ($rows as $row) {
            $list[] = (int) $row['id'];
        }

        return $list;
    }
}
