<?php
declare(strict_types = 1);

namespace Spipu\ProcessBundle\Repository;

use Spipu\ProcessBundle\Entity\Log;
use Spipu\ProcessBundle\Service\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method Log|null find($id, $lockMode = null, $lockVersion = null)
 * @method Log|null findOneBy(array $criteria, array $orderBy = null)
 * @method Log[]    findAll()
 * @method Log[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogRepository extends ServiceEntityRepository
{
    /**
     * @var Status
     */
    private $status;

    /**
     * ProcessLogRepository constructor.
     * @param RegistryInterface $registry
     * @param Status $status
     */
    public function __construct(
        RegistryInterface $registry,
        Status $status
    ) {
        parent::__construct($registry, Log::class);
        $this->status = $status;
    }

    /**
     * @param \DateTimeInterface $limitDate
     * @return int
     */
    public function deleteFinishedLogs(\DateTimeInterface $limitDate): int
    {
        $query = $this
            ->createQueryBuilder('l')
            ->delete()
            ->andWhere('l.status = (:status)')
            ->andWhere('l.updatedAt < (:date)')
            ->setParameter('status', $this->status->getFinishedStatus())
            ->setParameter('date', $limitDate)
            ->getQuery();

        return $query->execute();
    }
}
