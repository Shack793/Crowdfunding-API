<?php
namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CampaignController extends Controller
{
    // List all campaigns regardless of status
    public function getAllCampaigns()
    {
        try {
            $campaigns = Campaign::with(['category', 'user'])
                ->get();
            return response()->json($campaigns);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch campaigns', 'message' => $e->getMessage()], 500);
        }
    }

    // Show a single campaign by ID
    public function show($slug)
    {
        try {
            $campaign = Campaign::with(['category', 'user', 'rewards', 'media', 'comments', 'analytics'])
                ->where('slug', $slug)
                ->firstOrFail();
            return response()->json($campaign);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch campaign', 'message' => $e->getMessage()], 500);
        }
    }

    // Store a new campaign
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_id' => 'required|exists:users,id',
                'category_id' => 'required|exists:categories,id',
                'title' => 'required|string|max:255',
                'slug' => 'required|string|max:255|unique:campaigns,slug',
                'description' => 'required|string',
                'goal_amount' => 'required|numeric',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'thumbnail' => 'nullable|string',
                'visibility' => 'required|in:public,private,unlisted',
                'image_url' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            ]);
            $validated['status'] = 'active';
            // Remove image_url from validated if present, will be set below
            unset($validated['image_url']);
            $campaign = Campaign::create($validated);
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('public/campaigns');
                $imageUrl = \Illuminate\Support\Facades\Storage::url($path);
                $campaign->image_url = $imageUrl;
                $campaign->save();
            }
            return response()->json($campaign, 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to create campaign', 'message' => $e->getMessage()], 500);
        }
    }

    // Update an existing campaign
    public function update(Request $request, $slug)
    {
        try {
            $campaign = Campaign::where('slug', $slug)->firstOrFail();
            $validated = $request->validate([
                'category_id' => 'sometimes|exists:categories,id',
                'title' => 'sometimes|string|max:255',
                'slug' => 'sometimes|string|max:255|unique:campaigns,slug,' . $campaign->id,
                'description' => 'sometimes|string',
                'goal_amount' => 'sometimes|numeric',
                'current_amount' => 'sometimes|numeric',
                'start_date' => 'sometimes|date',
                'end_date' => 'sometimes|date|after_or_equal:start_date',
                'status' => 'sometimes|in:upcoming,running,pending,expired,cancelled,completed',
                'visibility' => 'sometimes|in:public,private,unlisted',
                'image_url' => 'nullable|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
                // 'thumbnail' => 'nullable|string', // Remove if deprecated
            ]);

            // Remove image_url from validated if present, will be set below
            unset($validated['image_url']);

            $campaign->update($validated);

            // Handle image upload if present
            if ($request->hasFile('image')) {
                $path = $request->file('image')->store('public/campaigns');
                $imageUrl = \Illuminate\Support\Facades\Storage::url($path);
                $campaign->image_url = $imageUrl;
                $campaign->save();
            }

            return response()->json($campaign);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to update campaign', 'message' => $e->getMessage()], 500);
        }
    }

    // Delete a campaign
    public function destroy($slug)
    {
        try {
            $campaign = Campaign::where('slug', $slug)->firstOrFail();
            $campaign->delete();
            return response()->json(['message' => 'Campaign deleted']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete campaign', 'message' => $e->getMessage()], 500);
        }
    }

    // Admin approve campaign
    public function approve($slug)
    {
        $campaign = Campaign::where('slug', $slug)->firstOrFail();
        $campaign->status = 'active';
        $campaign->save();
        return response()->json(['message' => 'Campaign approved', 'campaign' => $campaign]);
    }

    // Admin reject campaign
    public function reject($slug)
    {
        $campaign = Campaign::where('slug', $slug)->firstOrFail();
        $campaign->status = 'rejected';
        $campaign->save();
        return response()->json(['message' => 'Campaign rejected', 'campaign' => $campaign]);
    }

    // Invite user to private campaign
    public function invite(Request $request, $campaignId)
    {
        $validated = $request->validate([
            'email' => 'required|email',
        ]);
        $token = bin2hex(random_bytes(16));
        $expiry = now()->addDays(3);
        $invitation = \App\Models\CampaignInvitation::create([
            'campaign_id' => $campaignId,
            'email' => $validated['email'],
            'token' => $token,
            'expiry' => $expiry,
        ]);
        // TODO: Send email logic here
        return response()->json(['message' => 'Invitation sent', 'invitation' => $invitation]);
    }

    // Accept invitation
    public function acceptInvite($token)
    {
        $invitation = \App\Models\CampaignInvitation::where('token', $token)
            ->where('expiry', '>', now())
            ->firstOrFail();
        // TODO: Attach user to campaign_access
        $invitation->delete();
        return response()->json(['message' => 'Invitation accepted']);
    }

    // Revoke invitation
    public function revokeInvite($id)
    {
        $invitation = \App\Models\CampaignInvitation::findOrFail($id);
        $invitation->delete();
        return response()->json(['message' => 'Invitation revoked']);
    }

    // Get all campaigns (public endpoint, no userId)
    public function getUserCampaigns()
    {
        try {
            $campaigns = Campaign::with(['category', 'user'])
                ->latest()
                ->paginate(12);

            // Add boosted status and boost end date information
            $campaigns->getCollection()->transform(function ($campaign) {
                $campaign->is_boosted = $campaign->is_boosted;
                $campaign->boost_ends_at = $campaign->boost_ends_at;
                
                // Add a formatted boost end date for display
                if ($campaign->boost_ends_at) {
                    $campaign->boost_ends_at_formatted = $campaign->boost_ends_at->format('F j, Y H:i');
                }
                
                // Add days remaining until boost ends
                if ($campaign->boost_ends_at && $campaign->boost_ends_at->isFuture()) {
                    $campaign->boost_days_remaining = $campaign->boost_ends_at->diffInDays();
                }
                
                return $campaign;
            });

            return response()->json($campaigns);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch campaigns', 'message' => $e->getMessage()], 500);
        }
    }

    // List campaigns (paginated, filterable)
    public function index(Request $request)
    {
        $query = Campaign::with(['category', 'user']);
        // Optional: add filters (status, visibility, etc.)
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->has('visibility')) {
            $query->where('visibility', $request->input('visibility'));
        }
        $campaigns = $query->paginate(10);
        return response()->json($campaigns);
    }

    // Trending campaigns
    public function trending()
    {
        $threeMonthsAgo = now()->subMonths(3);
        $campaigns = \App\Models\Campaign::withCount(['contributions' => function($query) use ($threeMonthsAgo) {
            $query->where('created_at', '>=', $threeMonthsAgo);
        }])
            ->where('status', 'active')
            ->having('contributions_count', '>', 0)
            ->orderByDesc('contributions_count')
            ->orderByDesc('updated_at')
            ->paginate(9); // Use pagination

        return response()->json($campaigns);
    }

    /**
     * Show a single campaign by slug for the authenticated user (owner)
     */
    public function showUserCampaign($slug)
    {
        try {
            $campaign = Campaign::with(['category', 'user', 'rewards', 'media', 'comments', 'analytics'])
                ->where('slug', $slug)
                ->where('user_id', auth()->id())
                ->firstOrFail();
            return response()->json($campaign);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch campaign', 'message' => $e->getMessage()], 404);
        }
    }

    /**
     * Get all campaigns created by the authenticated user
     */
    public function userCampaigns()
    {
        try {
            $userId = auth()->id();
            $campaigns = Campaign::with(['category', 'user', 'rewards', 'media', 'comments', 'analytics'])
                ->where('user_id', $userId)
                ->latest()
                ->paginate(10);
            return response()->json($campaigns);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to fetch user campaigns', 'message' => $e->getMessage()], 500);
        }
    }
}
