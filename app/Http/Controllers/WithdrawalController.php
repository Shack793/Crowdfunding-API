<?php

namespace App\Http\Controllers;

use App\Models\Withdrawal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class WithdrawalController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'charge' => 'nullable|numeric',
            'internal_reference' => 'nullable|string',
            'client_reference' => 'nullable|string',
            'external_reference' => 'nullable|string',
            'transaction_reference' => 'nullable|string',
        ]);
        $withdrawal = Withdrawal::create(array_merge($validated, [
            'user_id' => Auth::id(),
            'status' => 'pending',
            'requested_at' => now(),
        ]));
        return response()->json($withdrawal, 201);
    }
}
