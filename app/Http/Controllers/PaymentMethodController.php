<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;

class PaymentMethodController extends Controller
{
    public function index()
    {
        $methods = PaymentMethod::where('is_active', true)->get();
        return response()->json($methods);
    }

    // For authenticated users
    public function authenticatedIndex()
    {
        // You can add extra logic for authenticated users here if needed
        $methods = PaymentMethod::where('is_active', true)->get();
        return response()->json([
            'authenticated' => true,
            'methods' => $methods
        ]);
    }

    // For unauthenticated (public) users
    public function publicIndex()
    {
        $methods = PaymentMethod::where('is_active', true)->get();
        return response()->json([
            'authenticated' => false,
            'methods' => $methods
        ]);
    }
}
