<?php

namespace App\Controller\Api;

use App\Entity\Order;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/orders', name: 'api_orders_')]
class OrderController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private OrderService $orderService
    ) {}

    #[Route('', name: 'all_order', methods: ['GET'])]
    public function getAllOrders(Request $request): JsonResponse
    {
        $filters = [
            'page' => (int)$request->query->get('page', 1),
            'limit' => (int)$request->query->get('limit', 10),
            'status' => $request->query->get('status'),
            'date_from' => $request->query->get('date_from'),
            'date_to' => $request->query->get('date_to'),
            'email' => $request->query->get('email'),
        ];

        $result = $this->orderService->getAllOrders($filters);

        return $this->json($result);
    }

    #[Route('/{id}', name: 'order_by_id', methods: ['GET'])]
    public function getOrderById(int $id): JsonResponse
    {
        $order = $this->orderService->getOrderById($id);
        if (!$order) return $this->json(['message' => 'Order not found'], 404);

        return $this->json($order);
    }

    #[Route('', name: 'create_order', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $order = $this->orderService->createOrder($data);
        return $this->json(['id' => $order->getId()], 201);
    }

    #[Route('/{id}', name: 'update_order', methods: ['PUT'])]
    public function update(Order $order, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $this->orderService->updateOrder($order, $data);
        return $this->json(['message' => 'Updated']);
    }

    #[Route('/{id}', name: 'delete_order', methods: ['DELETE'])]
    public function delete(Order $order): JsonResponse
    {
        $this->orderService->deleteOrder($order);
        return $this->json(['message' => 'Deleted']);
    }

    #[Route('/{id}/status', name: 'status_order', methods: ['PATCH'])]
    public function changeStatus(Order $order, Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $this->orderService->changeStatus($order, $data['status']);
        return $this->json(['message' => 'Status updated']);
    }
}
