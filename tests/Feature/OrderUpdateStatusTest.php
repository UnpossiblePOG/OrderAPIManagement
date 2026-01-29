<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Models\Order;

class OrderUpdateStatusTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_order_status_successfully()
    {
        $order = new Order;
        $order->order_number = 'ORD' . time() . rand(1000, 9999);
        $order->customer_name = 'Test User';
        $order->customer_email = 'test@example.com';
        $order->total_amount = 100.00;
        $order->status = 'Pending';
        $order->created_at = now();
        $order->save();

        $response = $this->putJson('/api/orders/' . $order->id, [
            'status' => 'Completed',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'status' => 'success',
                     'message' => 'Order is updated successfully.',
                 ]);
        
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'Completed',
        ]);
    }

    public function test_update_order_status_not_found()
    {
        $response = $this->putJson('/api/orders/999999', [
            'status' => 'Completed',
        ]);

        $response->assertStatus(500)
                 ->assertJson([
                     'status' => 'error',
                     'message' => 'Order ID is not present.',
                 ]);
    }
}
