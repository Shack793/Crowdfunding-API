<?php
namespace App\Http\Controllers;

use App\Models\Reward;
use Illuminate\Http\Request;

class RewardController extends Controller
{
public function index($campaignId)
{
$rewards = Reward::where('campaign_id', $campaignId)->get();
return response()->json($rewards);
}

public function store(Request $request)
{
$validated = $request->validate([
'campaign_id' => 'required|exists:campaigns,id',
'title' => 'required|string|max:255',
'description' => 'nullable|string',
'minimum_amount' => 'required|numeric',
]);
$reward = Reward::create($validated);
return response()->json($reward, 201);
}

public function update(Request $request, $id)
{
$reward = Reward::findOrFail($id);
$validated = $request->validate([
'title' => 'sometimes|string|max:255',
'description' => 'nullable|string',
'minimum_amount' => 'sometimes|numeric',
]);
$reward->update($validated);
return response()->json($reward);
}

public function destroy($id)
{
$reward = Reward::findOrFail($id);
$reward->delete();
return response()->json(['message' => 'Reward deleted']);
}
}
