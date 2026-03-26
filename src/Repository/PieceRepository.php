<?php

namespace App\Repository;

use App\Entity\Piece;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Piece>
 */
class PieceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Piece::class);
    }

    /*public function searchByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('p')
        ->where('LOWER(p.name) LIKE :kw') //ne tolère pas les fautes de frappe
        ->setParameter('kw', '%' . strtolower($keyword) . '%')
        ->getQuery()
        ->getResult();
    }*/

    public function findPaginated(int $offset, int $limit): array
    {
        return $this->createQueryBuilder('p')
            ->setFirstResult($offset)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countTotal(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findBySearchTerm(string $searchTerm): array
    {
        return $this->createQueryBuilder('p')
            ->where('LOWER(p.name) LIKE :term')
            ->setParameter('term', '%' . strtolower($searchTerm) . '%')
            ->getQuery()
            ->getResult();
    }

    public function findFuzzyByName(string $term): array
    {
        $pieces = $this->findAll();
        $results = [];
        foreach ($pieces as $piece) {
            // Tolérance : <= 2 caractères d'écart
            if (levenshtein(strtolower($term), strtolower($piece->getName())) <= 2) {
                $results[] = $piece;
            }
        }
        return $results;
    }

    public function searchAdvanced(
        ?string $query,
        ?int $categoryId,
        ?string $mode,
        ?float $minPrice,
        ?float $maxPrice,
        int $page,
        int $limit
    ): array {
        $qb = $this->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->addSelect('c');

        $this->applySearchFilters($qb, $query, $categoryId, $mode, $minPrice, $maxPrice);

        return $qb
            ->orderBy('p.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function countSearchAdvanced(
        ?string $query,
        ?int $categoryId,
        ?string $mode,
        ?float $minPrice,
        ?float $maxPrice
    ): int {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)');

        $this->applySearchFilters($qb, $query, $categoryId, $mode, $minPrice, $maxPrice);

        return (int) $qb->getQuery()->getSingleScalarResult();
    }

    private function applySearchFilters(
        \Doctrine\ORM\QueryBuilder $qb,
        ?string $query,
        ?int $categoryId,
        ?string $mode,
        ?float $minPrice,
        ?float $maxPrice
    ): void {
        if ($query !== null && trim($query) !== '') {
            $term = '%' . mb_strtolower(trim($query)) . '%';
            $qb
                ->andWhere('LOWER(p.name) LIKE :term OR LOWER(p.description) LIKE :term')
                ->setParameter('term', $term);
        }

        if ($categoryId !== null && $categoryId > 0) {
            $qb->andWhere('p.category = :categoryId')
                ->setParameter('categoryId', $categoryId);
        }

        if ($mode === 'vente') {
            $qb->andWhere('p.exchange = :isSale')->setParameter('isSale', true);
        } elseif ($mode === 'echange') {
            $qb->andWhere('p.exchange = :isSale')->setParameter('isSale', false);
        }

        if ($minPrice !== null) {
            $qb->andWhere('p.price >= :minPrice')->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('p.price <= :maxPrice')->setParameter('maxPrice', $maxPrice);
        }
    }
}
