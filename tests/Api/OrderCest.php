<?php
namespace App\Tests\Api;

use App\Tests\ApiTester;
use Codeception\Util\HttpCode;

class OrderCest
{
    public function testCreateOrder(ApiTester $I)
    {
        $I->wantTo('Create order');

        $orderData = [
            'customerName' => 'John Doe',
            'customerEmail' => 'jo123224132hn@example.com',
            'totalAmount' => 150.5,
            'items' => [
                ['productName' => 'Product 1', 'quantity' => 2, 'price' => 50],
                ['productName' => 'Product 2', 'quantity' => 1, 'price' => 50.5],
            ]
        ];

        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/api/orders', json_encode($orderData));

        $I->seeResponseCodeIs(HttpCode::CREATED);
        $I->seeResponseIsJson();

        codecept_debug($I->grabResponse());

        $I->seeResponseContainsJson([
            'id' => 36,
            'customer_name' => 'John Doe',
            'customer_email' => 'jo123224132hn@example.com',
            'total_amount' => 150.5,
            'status' => 'processing',
        ]);

        $I->seeResponseJsonMatchesJsonPath('$.items[0].product_name');
        $I->seeResponseJsonMatchesJsonPath('$.items[1].product_name');
    }

    public function testGetOrderById(ApiTester $I)
{
    $I->wantTo('Get order details by ID');

    $I->sendGET('/api/orders/29');

    $I->seeResponseCodeIs(HttpCode::OK);
    $I->seeResponseIsJson();

    $I->seeResponseContainsJson([
        'id' => 29,
        'customer_name' => 'John Doe',
        'customer_email' => 'jo241hn@example.com',
    ]);

    $I->seeResponseJsonMatchesJsonPath('$.items[0].product_name');
    $I->seeResponseJsonMatchesJsonPath('$.items[0].quantity');
    $I->seeResponseJsonMatchesJsonPath('$.items[0].price');
}

}
