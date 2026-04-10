<?php

namespace App\Repository;

use App\Entity\Stock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Stock>
 */
class StockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Stock::class);
    }

    public function save(Stock $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Stock $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all low stock items
     * @return Stock[]
     */
    public function findLowStock(): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.quantity <= s.minThreshold')
            ->andWhere('s.minThreshold IS NOT NULL')
            ->orderBy('s.quantity', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search stocks by item name, SKU, or supplier
     */
    public function search(?string $search = null, ?string $sort = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.supplier', 'supplier')
            ->addSelect('supplier');

        if ($search) {
            $qb->andWhere('s.itemName LIKE :search OR s.sku LIKE :search OR s.description LIKE :search OR supplier.name LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        $qb->orderBy('s.itemName', $sort);

        return $qb->getQuery()->getResult();
    }

    /**
     * Find stocks by supplier
     * @return Stock[]
     */
    public function findBySupplier(int $supplierId): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.supplier = :supplierId')
            ->setParameter('supplierId', $supplierId)
            ->orderBy('s.itemName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get total inventory value
     */
    public function getTotalInventoryValue(): float
    {
        $result = $this->createQueryBuilder('s')
            ->select('SUM(s.unitCost * s.quantity) as totalValue')
            ->where('s.unitCost IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }

    /**
     * Get stock count by supplier
     */
    public function getStockCountBySupplier(): array
    {
        return $this->createQueryBuilder('s')
            ->select('supplier.name as supplierName, COUNT(s.id) as itemCount, SUM(s.quantity) as totalQuantity')
            ->leftJoin('s.supplier', 'supplier')
            ->groupBy('supplier.id')
            ->orderBy('itemCount', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
