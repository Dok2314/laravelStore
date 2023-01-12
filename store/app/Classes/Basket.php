<?php

namespace App\Classes;

use App\Mail\OrderCreated;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Mail;

class Basket
{
    protected $order;

    public function __construct($createOrder = false)
    {
        $orderId = session('orderId');

        if(is_null($orderId) && $createOrder) {
            $this->order = Order::create([
                'user_id' => auth()->id()
            ]);
            session(['orderId' => $this->order->id]);
        } else {
            $this->order = Order::findOrFail($orderId);
        }
    }

    public function getOrder()
    {
        return $this->order;
    }

    public function countAvailable($updateCount = false)
    {
        foreach ($this->order->products as $orderProduct) {
            if($orderProduct->count < $this->getPivotRow($orderProduct)->count) {
                return false;
            }

            if($updateCount) {
                $orderProduct->count -= $this->getPivotRow($orderProduct)->count;
            }
        }

        if($updateCount) {
            $this->order->products->map->save();
        }

        return true;
    }

    public function saveOrder($phone, $email)
    {
        if(!$this->countAvailable(true)) {
            return false;
        }

        Mail::to($email)->send(new OrderCreated($this, $this->order));

        return $this->order->saveOrder($phone, $email);
    }

    public function removeProduct(Product $product)
    {
        if($this->order->products->contains($product->id)) {
            $pivotRow = $this->getPivotRow($product);

            if($pivotRow->count < 2) {
                $this->order->products()->detach($product->id);
            } else {
                $pivotRow->count--;
                $pivotRow->update();
            }
        } else {
            $this->order->products()->detach($product->id);
        }

        Order::changeFullSum(-$product->price);
    }

    public function addProduct(Product $product)
    {

        if($this->order->products->contains($product->id)) {
            $pivotRow = $this->getPivotRow($product);
            $pivotRow->count++;

            if($pivotRow->count > $product->count) {
                return false;
            }

            $pivotRow->update();
        } else {
            if($product->count == 0) {
                return false;
            }

            $this->order->products()->attach($product->id);
        }

        Order::changeFullSum($product->price);

        return true;
    }

    protected function getPivotRow(Product $product)
    {
        return $this->order->products->where('id', $product->id)->first()->pivot;
    }
}