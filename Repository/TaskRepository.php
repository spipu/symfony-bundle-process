<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Repository;

use Spipu\ProcessBundle\Entity\Task;
use Spipu\ProcessBundle\Service\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

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
    public function getIdsToExecuteAutomatically(int $failedMaxRetry): array
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
     * @param \DateTimeInterface $limitDate
     * @return int
     */
    public function deleteFinishedTasks(\DateTimeInterface $limitDate): int
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
}
