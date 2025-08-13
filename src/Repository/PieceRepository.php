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
}
