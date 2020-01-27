<?php

namespace App\Repository;

use App\Entity\AdDeal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method AdDeal|null find($id, $lockMode = null, $lockVersion = null)
 * @method AdDeal|null findOneBy(array $criteria, array $orderBy = null)
 * @method AdDeal[]    findAll()
 * @method AdDeal[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdDealRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AdDeal::class);
    }

    // /**
    //  * @return AdDeal[] Returns an array of AdDeal objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AdDeal
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
