<?php

namespace App\Repository;

use App\Entity\CustomerOrder;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

class CustomerOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerOrder::class);
    }

    /** Orders placed directly in the app (not created from a booking). */
    public function createStandaloneQueryBuilder(string $alias = 'o'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->andWhere(sprintf('%s.booking IS NULL', $alias));
    }

    public function countStandalone(array $criteria = []): int
    {
        $qb = $this->createStandaloneQueryBuilder('o')
            ->select('COUNT(o.id)');

        foreach ($criteria as $field => $value) {
            $qb->andWhere('o.'.$field.' = :'.$field)
                ->setParameter($field, $value);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * @return CustomerOrder[]
     */
    public function findStandaloneBy(array $orderBy, ?int $limit = null): array
    {
        $qb = $this->createStandaloneQueryBuilder('o');

        foreach ($orderBy as $field => $direction) {
            $qb->addOrderBy('o.'.$field, $direction);
        }

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        return $qb->getQuery()->getResult();
    }
}
