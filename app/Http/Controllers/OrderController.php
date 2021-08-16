<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public $user;
    public $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->user = auth('api')->user();
        $this->orderService = $orderService;
    }

    public function index()
    {
        return $this->user->orders()->with('orderhistories', 'orderinfo')->get();
    }


    public function store(Request $request)
    {


        return $this->orderService->create(
            $this->user,
            $request->shipping_charges ? $request->shipping_charges : 0,
            $request->promo,
            $request->commission,
            $request->discount ? $request->discount : 0,
            $request->shipping_method,
            $request->shipping_address,
            $request->city,
            $request->state,
            $request->pickup_location,
            $request->phoneNumber,
            $request->extra_instruction,
            $request->payment_method,
            $request->title,
            $request->isScheduled,
            $request->schedule_time
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function show(Order $order)
    {

        return $order->load('orderhistories', 'orderinfo');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function edit(Order $order)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Order $order)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Order  $order
     * @return \Illuminate\Http\Response
     */
    public function destroy(Order $order)
    {
        return $this->orderService->remove($order);
    }
}
