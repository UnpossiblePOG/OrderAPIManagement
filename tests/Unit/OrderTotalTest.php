<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Http\Controllers\Api\OrderController;

class OrderTotalTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_example(): void
    {
        $this->assertTrue(true);
    }
    public function test_it_calculates_total_sum_of_items(): void
    {
        // 1. Arrange: Setup the controller and dummy data
        $controller = new OrderController();
        $items = [
            ['product_name' => 'Ring', 'price' => 1000, 'quantity' => 1],
            ['product_name' => 'Watch',  'price' => 50,   'quantity' => 2],
        ];

        // 2. Act: Call the function
        $total = $controller->calculateOrderTotal($items);

        // 3. Assert: Check if (1000*1) + (50*2) = 1100
        $this->assertEquals(1100, $total);
    }
}
