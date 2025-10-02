<?php

namespace App\Service;

use App\Dto\CreateOrderDto;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Enum\OrderStatus;
use App\Interface\OrderInterface;
use App\Message\OrderNotificationMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;

use App\Repository\OrderRepository;

class OrderService implements OrderInterface
{
    public function __construct(
        private OrderRepository $orderRepository,
        private EntityManagerInterface $em,
        private MessageBusInterface $bus
    ) {}

    public function getAllOrders(array $filters): array
    {
        $page = $filters['page'] ?? 1;
        $limit = $filters['limit'] ?? 10;

        $ordersData = $this->findWithFilters(
            $page,
            $limit,
            $filters['status'] ?? null,
            $filters['date_from'] ?? null,
            $filters['date_to'] ?? null,
            $filters['email'] ?? null
        );

        $data = array_map(fn($order) => [
            'id' => $order->getId(),
            'customer_name' => $order->getCustomerName(),
            'customer_email' => $order->getCustomerEmail(),
            'total_amount' => $order->getTotalAmount(),
            'status' => $order->getStatus(),
            'created_at' => $order->getCreatedAt()->format('Y-m-d H:i:s')
        ], $ordersData['data']);

        return [
            'data' => $data,
            'total' => $ordersData['total'],
            'page' => $page,
            'limit' => $limit
        ];
    }

    public function getOrderById(int $id): array
    {
        $order = $this->orderRepository->find($id);

        if(!$order){
            throw new \Exception("Order with this id not found");
        }

        $items = [];
        foreach ($order->getItems() as $item) {
            $items[] = [
                'id' => $item->getId(),
                'product_name' => $item->getProductName(),
                'quantity' => $item->getQuantity(),
                'price' => $item->getPrice(),
            ];
        }

        return [
            'id' => $order->getId(),
            'customer_name' => $order->getCustomerName(),
            'customer_email' => $order->getCustomerEmail(),
            'total_amount' => $order->getTotalAmount(),
            'status' => $order->getStatus(),
            'items' => $items,
            'created_at' => $order->getCreatedAt()->format('Y-m-d H:i:s')
        ];
    }

    public function createOrder(CreateOrderDto $createOrderDto): Order
    {
        $userExist = $this->orderRepository->findBy(['customerEmail' => $createOrderDto->customerEmail]);

        if($userExist){
            throw new \Exception("User with this email already exists");
        }

        $order = new Order();
        $order->setCustomerName($createOrderDto->customerName);
        $order->setCustomerEmail($createOrderDto->customerEmail);
        $order->setTotalAmount($createOrderDto->totalAmount);

        foreach ($createOrderDto->items as $itemDto) {
            $item = new OrderItem();
            $item->setProductName($itemDto['productName']);
            $item->setQuantity($itemDto['quantity']);
            $item->setPrice($itemDto['price']);
            $order->addItem($item);
        }

        $this->orderRepository->saveOrder($order);

        $this->bus->dispatch(new OrderNotificationMessage($order->getId(), 'created'));

        return $order;
    }


    public function updateOrder(Order $order, array $data): Order
    {
        $order->setCustomerName($data['customer_name'] ?? $order->getCustomerName());
        $order->setCustomerEmail($data['customer_email'] ?? $order->getCustomerEmail());
        $order->setTotalAmount($data['total_amount'] ?? $order->getTotalAmount());
        $order->setUpdatedAt(new \DateTimeImmutable());

        $this->em->flush();
        return $order;
    }

    public function deleteOrder(Order $order): void
    {
        $this->em->remove($order);
        $this->em->flush();
    }

    public function changeStatus(Order $order, string $status): Order
    {
        $orderStatus = OrderStatus::from($status);
        $order->setStatus($orderStatus);
        $order->setUpdatedAt(new \DateTimeImmutable());
        $this->em->flush();

        $this->bus->dispatch(new OrderNotificationMessage($order->getId(), 'status_changed'));

        return $order;
    }

        public function findWithFilters(int $page, int $limit, ?string $status, ?string $dateFrom, ?string $dateTo, ?string $email): array
    {
        $qb = $this->orderRepository->createQueryBuilder('ord');

        if ($status) $qb->andWhere('ord.status = :status')->setParameter('status', $status);
        if ($dateFrom) $qb->andWhere('ord.createdAt >= :dateFrom')->setParameter('dateFrom', new \DateTime($dateFrom));
        if ($dateTo) $qb->andWhere('ord.createdAt <= :dateTo')->setParameter('dateTo', new \DateTime($dateTo));
        if ($email) $qb->andWhere('LOWER(ord.customerEmail) LIKE LOWER(:email)')->setParameter('email', '%' . strtolower($email) . '%');

        $qb->orderBy('ord.createdAt', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new Paginator($qb->getQuery(), true);
        return ['data' => iterator_to_array($paginator), 'total' => count($paginator)];
    }
}
