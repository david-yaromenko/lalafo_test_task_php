<?php

namespace App\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Enum\OrderStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

use App\Repository\OrderRepository;

class OrderService
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

        $ordersData = $this->orderRepository->findWithFilters(
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

    public function createOrder(array $data): Order
    {
        $order = new Order();
        $order->setCustomerName($data['customer_name']);
        $order->setCustomerEmail($data['customer_email']);
        $order->setTotalAmount($data['total_amount']);

        foreach ($data['items'] as $itemData) {
            $item = new OrderItem();
            $item->setOrder($order);
            $item->setProductName($itemData['product_name']);
            $item->setQuantity($itemData['quantity']);
            $item->setPrice($itemData['price']);
            $order->getItems()->add($item);
        }

        $this->em->persist($order);
        $this->em->flush();

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

        return $order;
    }
}
