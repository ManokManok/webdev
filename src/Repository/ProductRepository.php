<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 *
 * @method Product|null find($id, $lockMode = null, $lockVersion = null)
 * @method Product|null findOneBy(array $criteria, array $orderBy = null)
 * @method Product[]    findAll()
 * @method Product[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Search products - returns ALL products (no user-based filtering)
     * Both admin and staff can see all products regardless of who created them
     * 
     * @param string|null $term Search term for product name or issue
     * @param string $sort 'ASC'|'DESC'
     * @return Product[] All products matching the search criteria
     */
    public function search(?string $term, string $sort = 'ASC'): array
    {
        $qb = $this->createQueryBuilder('p');

        if ($term !== null && trim($term) !== '') {
            $qb->andWhere('LOWER(p.name) LIKE :t OR LOWER(p.issue) LIKE :t')
               ->setParameter('t', '%' . strtolower($term) . '%');
        }

        // No user filtering - returns all products for all users
        return $qb
            ->orderBy('p.id', strtoupper($sort) === 'DESC' ? 'DESC' : 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int,int> Map of category_id => product_count
     */
    public function countByCategory(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('IDENTITY(p.category) as cid, COUNT(p.id) as cnt')
            ->groupBy('cid');

        $rows = $qb->getQuery()->getArrayResult();

        $map = [];
        foreach ($rows as $r) {
            if ($r['cid'] === null) { continue; }
            $map[(int)$r['cid']] = (int)$r['cnt'];
        }
        return $map;
    }

    /**
     * Build a map of category_id => ['issue' => string, 'count' => int] for the most frequent issue in that category.
     * Categories without products are omitted.
     *
     * @return array<int,array{issue:string,count:int}>
     */
    public function topIssueByCategory(): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('IDENTITY(p.category) as cid, p.issue as issue, COUNT(p.id) as cnt')
            ->groupBy('cid, issue');

        $rows = $qb->getQuery()->getArrayResult();

        $top = [];
        foreach ($rows as $r) {
            if ($r['cid'] === null) { continue; }
            $cid = (int)$r['cid'];
            $cnt = (int)$r['cnt'];
            $issue = (string)$r['issue'];
            if (!isset($top[$cid]) || $cnt > $top[$cid]['count']) {
                $top[$cid] = ['issue' => $issue, 'count' => $cnt];
            }
        }

        return $top;
    }

    /**
     * Best-effort fallback: count products by brand name presence in Product.name.
     * @param string[] $names brand names (lowercase or mixed)
     * @return array<string,int> map brand_name_lower => count
     */
    public function countByBrandName(array $names): array
    {
        $map = [];
        foreach ($names as $name) {
            $needle = mb_strtolower((string)$name);
            if ($needle === '') { continue; }
            $cnt = (int)$this->createQueryBuilder('p')
                ->select('COUNT(p.id)')
                ->andWhere('LOWER(p.name) LIKE :needle')
                ->setParameter('needle', '%' . $needle . '%')
                ->getQuery()
                ->getSingleScalarResult();
            if ($cnt > 0) {
                $map[$needle] = $cnt;
            }
        }
        return $map;
    }

//    /**
//     * @return Product[] Returns an array of Product objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Product
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
