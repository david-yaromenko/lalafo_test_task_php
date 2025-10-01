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
        $qb = $this->createQueryBuilder('order');

        if ($status) $qb->andWhere('order.status = :status')->setParameter('status', $status);
        if ($dateFrom) $qb->andWhere('order.created_at >= :dateFrom')->setParameter('dateFrom', new \DateTime($dateFrom));
        if ($dateTo) $qb->andWhere('order.created_at <= :dateTo')->setParameter('dateTo', new \DateTime($dateTo));
        if ($email) $qb->andWhere('order.customer_email ILIKE :email')->setParameter('email', "%$email%");

        $qb->orderBy('order.created_at', 'DESC')
           ->setFirstResult(($page - 1) * $limit)
           ->setMaxResults($limit);

        $paginator = new Paginator($qb->getQuery(), true);
        return ['data' => iterator_to_array($paginator), 'total' => count($paginator)];
    }
}
