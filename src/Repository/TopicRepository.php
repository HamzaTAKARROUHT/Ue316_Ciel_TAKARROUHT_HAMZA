<?php
namespace App\Repository;

use App\Entity\Topic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TopicRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Topic::class);
    }

    public function findAllOrderedByDate()
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}