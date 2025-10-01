<?php

namespace App\MessageHandler;

use App\Message\OrderNotificationMessage;
use App\Repository\OrderRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;


class EmailNotificationHandler
{
    public function __construct(
        private MailerInterface $mailer,
        private OrderRepository $orderRepository,
        private LoggerInterface $logger
    ) {}

    public function __invoke(OrderNotificationMessage $message)
    {
        $order = $this->orderRepository->find($message->orderId);
        if (!$order) {
            $this->logger->warning("Order {$message->orderId} not found for email notification.");
            return;
        }

        $email = new Email();
        $email->to($order->getCustomerEmail())
              ->from('davidyaremenko@gmail.com');

        if ($message->type === 'created') {
            $email->subject('Дякуємо за ваше замовлення!')
                  ->text("Привіт {$order->getCustomerName()}, дякуємо за замовлення №{$order->getId()} на суму {$order->getTotalAmount()}.");
            $this->logger->info("Sent welcome email for order {$order->getId()}");

        } elseif ($message->type === 'status_changed') {
            $status = $order->getStatus()->value;
            if ($status === 'shipped') {
                $email->subject('Ваше замовлення відправлено!')
                      ->text("Привіт {$order->getCustomerName()}, ваше замовлення №{$order->getId()} відправлено.");
                $this->logger->info("Sent shipped email for order {$order->getId()}");
            } elseif ($status === 'delivered') {
                $email->subject('Дякуємо за покупку!')
                      ->text("Привіт {$order->getCustomerName()}, ваше замовлення №{$order->getId()} доставлено. Дякуємо!");
                $this->logger->info("Sent delivered email for order {$order->getId()}");
            } else {
                $this->logger->info("No email needed for status '{$status}' on order {$order->getId()}");
                return;
            }
        }

        try {
            $this->mailer->send($email);
        } catch (\Exception $e) {
            $this->logger->error("Failed to send email for order {$order->getId()}: ".$e->getMessage());
        }
    }
}
