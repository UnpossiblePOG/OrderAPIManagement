<?php
use App\Http\Controllers\Api\OrderController;
Route::get('orders', [OrderController::class, 'index']);
Route::get('orders/{page_no}', [OrderController::class, 'index']);
Route::post('orders', [OrderController::class, 'store']);
Route::put('orders/{id}', [OrderController::class, 'update']);
?>