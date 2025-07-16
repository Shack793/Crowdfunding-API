<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contribution;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ContributionApprovalController extends Controller
{
    public function approveContribution($id)
    {
        try {
            $contribution = Contribution::findOrFail($id);
            $contribution->status = 'successful';
            $contribution->save();

            return response()->json([
                'message' => 'Contribution approved successfully',
                'contribution' => $contribution
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Contribution not found'], 404);
        }
    }
}
