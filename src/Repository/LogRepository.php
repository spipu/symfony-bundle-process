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

use DateTimeInterface;
use Spipu\ProcessBundle\Entity\Log;
use Spipu\ProcessBundle\Service\Status;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Log|null find($id, $lockMode = null, $lockVersion = null)
 * @method Log|null findOneBy(array $criteria, array $orderBy = null)
 * @method Log[]    findAll()
 * @method Log[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class LogRepository extends ServiceEntityRepository
{
    private Status $status;

    public function __construct(
        ManagerRegistry $registry,
        Status $status
    ) {
        parent::__construct($registry, Log::class);
        $this->status = $status;
    }

    public function deleteFinishedLogs(DateTimeInterface $limitDate): int
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
