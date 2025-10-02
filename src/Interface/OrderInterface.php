<?php

namespace App\Interface;

use App\Dto\CreateOrderDto;
use App\Entity\Order;

interface OrderInterface
{

    public function findWithFilters(int $page, int $limit, ?string $status, ?string $dateFrom, ?string $dateTo, ?string $email): array;

    public function createOrder(CreateOrderDto $createOrderDto): Order;
}
