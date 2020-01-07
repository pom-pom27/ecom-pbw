<?php

namespace App\Http\Controllers;

use App\Consumer;
use App\Order;
use App\Produk;
use App\Http\Requests\OrderRequest;
use App\Http\Requests\OrderUpdateRequest;
use Barryvdh\DomPDF\Facade as PDF;
use Carbon\Carbon;

class OrderController extends Controller
{
    public function index() {
        if (request()->has('status_order')) {
            $orders = Order::where('status_order', request('status_order'))
                                ->paginate(5)
                                ->appends( 'status_order', request('status_order'));
        }
        elseif (request()->has('produk')) {
           $orders = Order::where('produk_id', request('produk'))
                                ->paginate(5)
                                ->appends( 'produk', request('produk'));
        }
        else{
            $orders = Order::paginate(5);
        }

        return view('order.index')->with('orders', $orders)->with('produks', Produk::all());
    }


    public function create() {
        return view('order.create')
                ->with('consumers', Consumer::all())
                ->with('produks', Produk::all());
    }


    public function store(OrderRequest $request) {
        $produk = Produk::find($request->produk);

        Order::create([
            'qty' => $request->qty,
            'consumer_id' => $request->consumer,
            'produk_id' => $request->produk,
            'total_harga' => $request->qty * $produk->harga
        ]);

        session()->flash('success', 'Order berhasil tersimpan.');

        return back();
    }


    public function update(OrderUpdateRequest $request, Order $order) {
        if ($request->status_order > 0) {
            if ($request->status_order == 3) {
                $produk = Produk::find($order->produk_id);

                if ($produk->stok >= $order->qty) {
                    $produk->stok = $produk->stok - (int)$order->qty;
                    $produk->save();
                }else{
                    session()->flash('error', 'Stok produk sudah habis');
                    return back();
                }
            }

            $status_orderToInt = (int)$request->status_order;
            $order->status_order = $status_orderToInt;

            $order->save();

            session()->flash('success', 'Status order berhasil dirubah.');
        }else{
            $order->delete();

            session()->flash('success', 'Order berhasil terhapus.');
        }
        return back();
    }

    public function pdf() {
        $orders= Order::where('status_order', 3)
                ->whereBetween('updated_at', [
                    Carbon::now()->startOfMonth(),
                    Carbon::now()->endOfMonth()
                ])
                ->orderBy('created_at', 'asc')
                ->get();

        $pdf = PDF::loadView('laporan.pdf', compact('orders'));
        return $pdf->stream('laporan-ecomm.pdf');
    }
}
