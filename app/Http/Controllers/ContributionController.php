<?php
namespace App\Http\Controllers;

use App\Models\Contribution;
use App\Models\Campaign;
use App\Models\Wallet;
use App\Events\ContributionMade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;

class ContributionController extends Controller
{
    public function store(Request $request, $campaignSlug)
    {
        // Validate the request data
        if (!Auth::check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        try {
            $validated = $request->validate([
                'payment_method_id' => 'required|exists:payment_methods,id',
                'amount' => 'required|numeric|min:1',
            ]);
            $contribution = null;
            \DB::transaction(function () use ($campaignSlug, $validated, &$contribution) {
                $campaign = Campaign::lockForUpdate()->where('slug', $campaignSlug)->firstOrFail();
                $contribution = Contribution::create([
                    'campaign_id' => $campaign->id,
                    'user_id' => Auth::user()->id,
                    'payment_method_id' => $validated['payment_method_id'],
                    'amount' => $validated['amount'],
                    'status' => 'pending',
                    'contribution_date' => now(),
                ]);
            });
            return response()->json([
                'contribution' => $contribution
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Campaign not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get recent donation statistics for a campaign
     *
     * @param  int  $campaignId
     * @return \Illuminate\Http\JsonResponse
     */
    public function recentDonations(string $campaignSlug)
    {
        try {
            $campaign = Campaign::with('contributions')->where('slug', $campaignSlug)->firstOrFail();

            $data = [
                'totalRaised' => $campaign->contributions()->sum('amount'),
                'goalAmount' => $campaign->goal_amount,
                'totalDonations' => $campaign->contributions()->count(),
                'recentDonations' => $campaign->contributions()
                    ->where('created_at', '>=', now()->subMonth())
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(),
                'topDonation' => $campaign->contributions()
                    ->orderBy('amount', 'desc')
                    ->first(),
                'firstDonation' => $campaign->contributions()
                    ->orderBy('created_at', 'asc')
                    ->first()
            ];

            return response()->json($data);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Campaign not found'], 404);
        }
    }

    public function index()
    {
        try {
            $contributions = Contribution::with('campaign')
                ->where('user_id', Auth::id())
                ->get();
            return response()->json($contributions);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function show($id)
    {
        try {
            $contribution = Contribution::with('campaign')
                ->where('user_id', Auth::id())
                ->findOrFail($id);
            return response()->json($contribution);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Contribution not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function donate(Request $request, $campaignSlug)
    {
        try {
            // Base validation rules for all users
            $validationRules = [
                'payment_method_id' => 'required|exists:payment_methods,id',
                'amount' => 'required|numeric|min:1',
            ];
            // Additional validation rules for guest users only
            if (!Auth::check()) {
                $validationRules = array_merge($validationRules, [
                    'name' => 'required|string|max:255',
                    'email' => 'required|email|max:255'
                ]);
            }
            $validated = $request->validate($validationRules);
            $contribution = null;
            \DB::transaction(function () use ($campaignSlug, $validated, &$contribution) {
                $campaign = Campaign::lockForUpdate()->where('slug', $campaignSlug)->firstOrFail();
                $contributionData = [
                    'campaign_id' => $campaign->id,
                    'user_id' => Auth::check() ? Auth::id() : null,
                    'payment_method_id' => $validated['payment_method_id'],
                    'amount' => $validated['amount'],
                    'system_reference' => \Illuminate\Support\Str::uuid(),
                    'status' => 'pending',
                    'contribution_date' => now(),
                ];
                if (!Auth::check()) {
                    $contributionData['name'] = $validated['name'];
                    $contributionData['email'] = $validated['email'];
                }
                $contribution = Contribution::create($contributionData);
            });
            return response()->json([
                'message' => 'Donation successful',
                'contribution' => $contribution
            ], 201);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Campaign not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle authenticated user donations
     */
    public function authenticatedDonate(Request $request, $campaignSlug)
    {
        try {
            $campaign = Campaign::with('user')->where('slug', $campaignSlug)->firstOrFail();
            
            $validated = $request->validate([
                'payment_method_id' => 'required|exists:payment_methods,id',
                'amount' => 'required|numeric|min:1',
            ]);

            $contribution = Contribution::create([
                'campaign_id' => $campaign->id,
                'user_id' => Auth::id(),
                'name' => Auth::user()->name, // store authenticated user's name
                'payment_method_id' => $validated['payment_method_id'],
                'amount' => $validated['amount'],
                'system_reference' => \Illuminate\Support\Str::uuid(),
                'status' => 'pending',
                'contribution_date' => now(),
            ]);

            // Fire the ContributionMade event
            event(new ContributionMade(
                $contribution,
                $campaign,
                $campaign->user,
                Auth::user()->name
            ));

            return response()->json([
                'message' => 'Donation successful',
                'contribution' => $contribution
            ], 201);

        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Campaign not found.'], 404);
        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Handle guest user donations
     */
    public function guestDonate(Request $request, $campaignSlug)
    {
        try {
            $validated = $request->validate([
                'payment_method_id' => 'required|exists:payment_methods,id',
                'amount' => 'required|numeric|min:1',
                'name' => 'required|string',
                'email' => 'required|email',
            ]);

            $contribution = \DB::transaction(function () use ($campaignSlug, $validated) {
                $campaign = Campaign::with('user.wallet')->where('slug', $campaignSlug)->firstOrFail();
                
                if (!$campaign->user || !$campaign->user->wallet) {
                    throw new \Exception('Campaign owner wallet not found');
                }

                $contribution = Contribution::create([
                    'campaign_id' => $campaign->id,
                    'user_id' => null, // guest
                    'name' => $validated['name'], // store guest name
                    'wallet_id' => $campaign->user->wallet->id, // campaign owner's wallet
                    'payment_method_id' => $validated['payment_method_id'],
                    'amount' => $validated['amount'],
                    'system_reference' => (string) \Illuminate\Support\Str::uuid(),
                    'status' => 'pending',
                    'contribution_date' => now(),
                ]);

                // Update campaign owner's wallet balance
                $campaign->user->wallet->increment('balance', $validated['amount']);

                // Fire the ContributionMade event
                event(new ContributionMade(
                    $contribution,
                    $campaign,
                    $campaign->user,
                    $validated['name']
                ));

                return $contribution;
            });

            return response()->json([
                'success' => true,
                'message' => 'Donation successful',
                'contribution' => $contribution
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to process donation: ' . $e->getMessage()
            ], 500);
        }
    }

    public function contributionStats()
    {
        $userId = Auth::id();

        $totalContributed = Contribution::where('user_id', $userId)->sum('amount');
        $campaignsSupported = Contribution::where('user_id', $userId)->distinct('campaign_id')->count('campaign_id');
        $averageContribution = Contribution::where('user_id', $userId)->avg('amount');
        

        $thisMonthContributions = Contribution::where('user_id', $userId)
            ->whereMonth('created_at', now()->month)
            ->count();

        return response()->json([
            'total_contributed' => $totalContributed,
            'campaigns_supported' => $campaignsSupported,
            'average_contribution' => $averageContribution,
            'this_month_contributions' => $thisMonthContributions,
        ]);
    }

    /**
     * Get mobile wallet holder name via UniWallet API
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getWalletHolderName(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'msisdn' => 'required|string|regex:/^[0-9+\-\s()]+$/',
                'network' => 'required|string|in:MTN,VODAFONE,ARTLTIGO',
            ]);

            // Clean the phone number (remove spaces, dashes, etc.)
            $cleanMsisdn = preg_replace('/[^0-9+]/', '', $validated['msisdn']);
            
            // Convert to standard format (ensure it starts with 233)
            if (strpos($cleanMsisdn, '0') === 0) {
                $cleanMsisdn = '233' . substr($cleanMsisdn, 1);
            } elseif (strpos($cleanMsisdn, '233') !== 0) {
                $cleanMsisdn = '233' . $cleanMsisdn;
            }

            // Prepare the payload for UniWallet API
            $payload = [
                'productId' => 4,
                'merchantId' => 1457,
                'apiKey' => 'u2m0tblpemgr3e2ud9c21oqfe2ftqo4j',
                'msisdn' => $cleanMsisdn,
                'network' => $validated['network']
            ];

            // Log the request for debugging
            \Log::channel('single')->info('UniWallet name enquiry request', [
                'payload' => $payload,
                'original_msisdn' => $validated['msisdn']
            ]);

            // Make the API call to UniWallet
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ])
                ->post('https://uniwallet.transflowitc.com/uniwallet/name/enquiry', $payload);

            // Log the response
            \Log::channel('single')->info('UniWallet name enquiry response', [
                'status' => $response->status(),
                'body' => $response->body()
            ]);

            $responseData = $response->json();

            // Check if the API call was successful
            if ($response->successful() && isset($responseData['responseCode'])) {
                if ($responseData['responseCode'] === '01') {
                    // Success response
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'name' => $responseData['name'] ?? null,
                            'msisdn' => $cleanMsisdn,
                            'network' => $validated['network'],
                            'responseMessage' => $responseData['responseMessage'] ?? 'Operation Successful'
                        ]
                    ]);
                } else {
                    // API returned an error code
                    return response()->json([
                        'success' => false,
                        'message' => $responseData['responseMessage'] ?? 'Name enquiry failed',
                        'responseCode' => $responseData['responseCode']
                    ], 400);
                }
            }

            // If we get here, the API call failed
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to wallet service',
                'error' => 'API call unsuccessful'
            ], 500);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input data',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            // Log the error
            \Log::channel('single')->error('UniWallet name enquiry error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching wallet holder name',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
