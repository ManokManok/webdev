<?php

namespace App\Repository;

use App\Entity\ActivityLog;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActivityLog>
 *
 * @method ActivityLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActivityLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActivityLog[]    findAll()
 * @method ActivityLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActivityLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityLog::class);
    }

    public function getQueryBuilder(array $filters = []): QueryBuilder
    {
        $qb = $this->createQueryBuilder('l')
            ->orderBy('l.createdAt', 'DESC');

        if (!empty($filters['user'])) {
            $qb->andWhere('l.user = :user')
               ->setParameter('user', $filters['user']);
        }

        if (!empty($filters['action'])) {
            $qb->andWhere('l.action = :action')
               ->setParameter('action', $filters['action']);
        }

        if (!empty($filters['entity'])) {
            $qb->andWhere('l.entity = :entity')
               ->setParameter('entity', $filters['entity']);
        }

        if (!empty($filters['dateFrom'])) {
            $qb->andWhere('l.createdAt >= :dateFrom')
               ->setParameter('dateFrom', $filters['dateFrom']);
        }

        if (!empty($filters['dateTo'])) {
            $dateTo = clone $filters['dateTo'];
            $dateTo->modify('+1 day');
            $qb->andWhere('l.createdAt < :dateTo')
               ->setParameter('dateTo', $dateTo);
        }

        return $qb;
    }

}
