<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index()
    {
        $transactions = Transaction::whereHas('contribution', function($q) {
            $q->where('user_id', Auth::id());
        })->with('contribution')->get();
        return response()->json($transactions);
    }
}
