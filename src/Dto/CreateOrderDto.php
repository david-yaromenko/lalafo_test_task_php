<?php

namespace App\Dto;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;


class CreateOrderDto
{
    #[SerializedName('customer_name')]
    #[Assert\NotBlank(message: "Customer name is required.")]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: "Customer name must be at least characters long",
        maxMessage: "Customer name cannot be longer than characters"
    )]
    public ?string $customerName = null;

    #[SerializedName('customer_email')]
    #[Assert\NotBlank(message: "Customer email is required.")]
    #[Assert\Email(message: "The email is not valid.")]
    public ?string $customerEmail = null;

    #[SerializedName('total_amount')]
    #[Assert\NotBlank(message: "Total amount is required.")]
    #[Assert\Positive(message: "Total amount must be a positive number.")]
    public ?float $totalAmount = null;

    #[Assert\Count(min: 1, minMessage: 'You must add at least one item')]
    #[Assert\All([
        new Assert\Collection([
            'productName' => [new Assert\NotBlank()],
            'quantity' => [new Assert\NotBlank(), new Assert\Positive()],
            'price' => [new Assert\NotBlank(), new Assert\Positive()],
        ])
    ])]
    public array $items = [];
}
