<?php
namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'campaign_id' => 'required|exists:campaigns,id',
            'comment' => 'required|string',
        ]);
        $comment = Comment::create([
            'user_id' => Auth::id(),
            'campaign_id' => $validated['campaign_id'],
            'comment' => $validated['comment'],
            'status' => 'pending',
            'created_at' => now(),
        ]);
        return response()->json($comment, 201);
    }

    public function index()
    {
        $campaignIds = Campaign::where('user_id', Auth::id())->pluck('id');
        $comments = Comment::with('user', 'campaign')
            ->whereIn('campaign_id', $campaignIds)
            ->get();
        return response()->json($comments);
    }

    public function stats()
    {
        $totalComments = Comment::where('user_id', Auth::id())->count();
        $commentsThisMonth = Comment::where('user_id', Auth::id())
            ->whereMonth('created_at', now()->month)
            ->count();

        return response()->json([
            'total_comments' => $totalComments,
            'this_month_comments' => $commentsThisMonth,
        ]);
    }
}
