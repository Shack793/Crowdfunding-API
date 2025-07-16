<?php

namespace App\Http\Controllers;

use App\Models\Subscriber;
use Illuminate\Http\Request;

class SubscriberController extends Controller
{
public function store(Request $request)
{
$validated = $request->validate([
'email' => 'required|email|unique:subscribers,email',
]);
$subscriber = Subscriber::create([
'email' => $validated['email'],
'subscribed_at' => now(),
]);
return response()->json($subscriber, 201);
}
}
