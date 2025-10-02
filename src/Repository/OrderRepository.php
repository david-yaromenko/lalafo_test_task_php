<?php

namespace App\Repository;

use App\Entity\Order;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Tools\Pagination\Paginator;

class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findWithFilters(int $page, int $limit, ?string $status, ?string $dateFrom, ?string $dateTo, ?string $email): array
    {
        $qb = $this->createQueryBuilder('ord');

        if ($status) $qb->andWhere('ord.status = :status')->setParameter('status', $status);
        if ($dateFrom) $qb->andWhere('ord.createdAt >= :dateFrom')->setParameter('dateFrom', new \DateTime($dateFrom));
        if ($dateTo) $qb->andWhere('ord.createdAt <= :dateTo')->setParameter('dateTo', new \DateTime($dateTo));
        if ($email) $qb->andWhere('ord.customerEmail ILIKE :email')->setParameter('email', "%$email%");

        $qb->orderBy('ord.createdAt', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $paginator = new Paginator($qb->getQuery(), true);
        return ['data' => iterator_to_array($paginator), 'total' => count($paginator)];
    }
}
