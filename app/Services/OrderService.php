<?php

namespace App\Services;

use App\Http\Controllers\BankDetailController;
use App\Models\Order;
use App\Models\Product;
use App\Notifications\OrderCreated;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class OrderService
{

  public function generateUniqueCode()
  {
    $code= null;
    do {
      $code = random_int(10000000, 99999999);
    } while (Order::where("order_no", "=", $code)->first());

    return $code;
  }


  public function create(
    $user,
    $name,
    $shipping_charges,
    $promo,
    $commission,
    $discount,
    $shipping_method,
    $shipping_address,
    $city,
    $state,
    $pickup_location,
    $phoneNumber,
    $extra_instruction,
    $payment_method,
    $title,
    $isScheduled,
    $schedule_time,
    $delivery_method,
    $contact,
    $contact_email


  ) {

    // try {
    return  DB::transaction(function () use (
      $user,
      $name,
      $shipping_charges,
      $promo,
      $discount,
      $shipping_method,
      $shipping_address,
      $city,
      $state,
      $phoneNumber,
      $extra_instruction,
      $payment_method,
      $pickup_location,
      $commission,
      $title,
      $isScheduled,
      $schedule_time,
      $delivery_method,
      $contact,
      $contact_email
    ) {
      $cartservice = new CartService;
      $usercart =  $cartservice->getCart($user)['cart'];

      if (!count($usercart)) {
        return response()->json(
          [
            'status' => false,
            'message' => 'Empty cart',

          ],
          401
        );
      }
      $total = $cartservice->total($user)['total'];
      $weight = $cartservice->total($user)['weight'];
      $order_no = $this->generateUniqueCode();

      $grand_total = (intval($total) + intval($shipping_charges) + intval($commission)) - $discount;

      $items = $usercart->map(function ($a) {
        return $a['quantity'];
      })->reduce(function ($a, $b) {
        return $a + $b;
      });

      if ($shipping_method === 'schedule') {
        $isScheduled = true;
      }
      //create order
      $order =  $user->orders()->create([
        'order_no' => $order_no,
        'name' => $name,
        'status' => 'pending',
        'sub_total' => $total,
        'total_amount' => $total,
        'commission' => $commission,
        'tax' => 0,
        'shipping_charges' => $shipping_charges,
        'promo' => $promo,
        'discount' => $discount,
        'grand_total' => $grand_total,
        'title' => $title,
        'isScheduled' => $isScheduled,
        'schedule_time' => $schedule_time,
        'items' => $items,
        'shipping_method' => $shipping_method,
        'weight'=> $weight

      ]);

      $order->orderhistories()->createMany($usercart->toArray());

      $mappedarray = array_map(function ($a) use ($order_no) {
        $a['order_no'] = $order_no;
        return $a;
      }, $usercart->toArray());
      $user->storeorder()->createMany($mappedarray);
      $this->reducequantity($usercart);
      //update order information


      $order->orderinfo()->create([
        'user_id' => $user->id,
        'firstName' => $user->firstName,
        'lastName' => $user->lastName,
        'delivery_method' => $delivery_method,
        'shipping_method' => $shipping_method,
        'shipping_address' => $shipping_address,
        '$pickup_location' => $pickup_location,
        'email' => $user->email,
        'city' => $city,
        'state' => $state,
        'phoneNumber' => $phoneNumber,
        'extra_instruction' => $extra_instruction,
        'payment_method' => $payment_method
      ]);

      //update user profile here

      $address = $user->address;

      array_push($address, [
        'address' => $shipping_address,
        'city' => $city,
        'state' => $state,
        'phoneNumber' => $phoneNumber,
        'contact' => $contact,
        'contact_email'=> $contact_email
      ]);
      $user->address =  $address;
      $user->save();


      $myrequest = new Request();
      $myrequest->setMethod('POST');
      $myrequest->request->add([
        'amount' => $grand_total,
        'email' => $user->email,
        'order_id' => $order->id
      ]);


      $payment  = new BankDetailController();
      $payment_data = $payment->makepayment($myrequest);



      // clear cart
      // $cartservice->clearcart($user);


      return response()->json(
        [
          'status' => true,
          'message' => 'order created',
          'data' => $payment_data,
          'order' => $order
        ],
        201
      );
    });
    // } catch (\Throwable $th) {
    //   return response()->json(
    //     [
    //       'status' => false,
    //       'message' => $th
    //     ],
    //     200
    //   );
    // }
  }

  public function reducequantity($cartitems)
  {

    foreach ($cartitems as $item) {
     $product  = Product::find($item->product_id);
     $product->in_stock = $product->in_stock  - $item->quantity;
     $product->save();
    }
  }

  public function remove($order)
  {
    $order->delete();
    return response()->json(
      [
        'status' => true,
        'message' => 'order deleted'
      ],
      200
    );
  }
}
