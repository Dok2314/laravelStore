<?php

namespace App\Classes;

use App\Mail\OrderCreated;
use App\Models\Order;
use App\Models\Product;
use App\Services\CurrencyConversion;
use Illuminate\Support\Facades\Mail;

class Basket
{
    protected $order;

    public function __construct($createOrder = false)
    {
        $order = session('order');

        if(is_null($order) && $createOrder) {
            $this->order = Order::create([
                'user_id'       => auth()->id(),
                'currency_id'   => CurrencyConversion::getCurrentCurrencyFromSession()->id,
                'sum'           => rand(100, 200),
                'price'         => 214124,
            ]);

            session(['order' => $this->order]);
        } else {
            $this->order = $order;
        }
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function countAvailable($updateCount = false)
    {
        $products = collect([]);

        foreach ($this->order->products as $orderProduct) {
            $product = Product::find($orderProduct->id);

            if($orderProduct->countInOrder > $product->count) {
                return false;
            }

            if($updateCount) {
                $product->count -= $orderProduct->countInOrder;
                $products->push($product);
            }
        }

        if($updateCount) {
            $products->map->save();
        }

        return true;
    }

    public function saveOrder($phone, $email)
    {
        if(!$this->countAvailable(true)) {
            return false;
        }

        $this->order->saveOrder($phone, $email);

        Mail::to($email)->send(new OrderCreated($this, $this->order));

        return true;
    }

    public function removeProduct(Product $product)
    {
        if($this->order->products->contains($product)) {
            $pivotRow = $this->order->products->where('id', $product->id)->first();

            if($pivotRow->countInOrder < 2) {
                $this->order->products->pop($product->id);
            } else {
                $pivotRow->countInOrder--;
            }
        }
    }

    public function addProduct(Product $product)
    {
        if($this->order->products->contains($product)) {
            $pivotRow = $this->order->products->where('id', $product->id)->first();

            if($pivotRow->countInOrder >= $product->count) {
                return false;
            }

            $pivotRow->countInOrder++;
        } else {
            if($product->count == 0) {
                return false;
            }

            $product->countInOrder = 1;

            $this->order->products->push($product);
        }

        return true;
    }
}
