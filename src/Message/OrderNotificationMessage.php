<?php

namespace App\Message;

class OrderNotificationMessage
{
    public function __construct(
        public int $orderId,
        public string $type
    ) {}
}
