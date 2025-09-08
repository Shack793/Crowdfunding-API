<?php

namespace App\Filament\Pages;

use App\Models\WithdrawalFee;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AdminWithdrawal extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?string $navigationLabel = 'Withdraw Fees';
    protected static string $view = 'filament.pages.admin-withdrawal';
    protected static ?int $navigationSort = 10;

    public static function canAccess(): bool
    {
        // Allow any authenticated admin user for testing
        // In production, you might want to restrict this to specific roles
        return Auth::check();
    }

    public ?array $data = [];
    public $totalAvailable = 0;
    public $pendingTransactions = 0;

    public function mount(): void
    {
        $this->totalAvailable = WithdrawalFee::where('status', 'calculated')->sum('fee_amount');
        $this->pendingTransactions = WithdrawalFee::where('status', 'calculated')->count();
        
        $this->form->fill([
            'amount' => '', // Let admin enter the amount they want to withdraw
            'network' => 'MTN',
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Withdrawal Summary')
                    ->description('Review the fees available for withdrawal')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Placeholder::make('total_available')
                                    ->label('Total Available')
                                    ->content(fn () => 'GHS ' . number_format($this->totalAvailable, 2)),
                                    
                                Placeholder::make('pending_count')
                                    ->label('Pending Transactions')
                                    ->content(fn () => $this->pendingTransactions . ' transactions'),
                            ]),
                    ]),
                    
                Section::make('Mobile Money Details')
                    ->description('Enter your mobile money details for withdrawal')
                    ->schema([
                        TextInput::make('amount')
                            ->label('Withdrawal Amount (GHS)')
                            ->required()
                            ->numeric()
                            ->prefix('GHS')
                            ->placeholder('Enter amount to withdraw')
                            ->helperText(fn () => "Maximum available: GHS " . number_format($this->totalAvailable, 2))
                            ->suffixAction(
                                \Filament\Forms\Components\Actions\Action::make('setMaxAmount')
                                    ->icon('heroicon-m-arrow-up-circle')
                                    ->tooltip('Set maximum available amount')
                                    ->action(function ($set) {
                                        $set('amount', number_format($this->totalAvailable, 2));
                                    })
                            )
                            ->rules([
                                'required',
                                'numeric',
                                'min:0.01',
                                fn () => function (string $attribute, $value, \Closure $fail) {
                                    if ($value > $this->totalAvailable) {
                                        $fail("The withdrawal amount cannot exceed the available amount of GHS " . number_format($this->totalAvailable, 2));
                                    }
                                },
                            ])
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set) {
                                // Ensure the amount doesn't exceed available
                                if ($state && $state > $this->totalAvailable) {
                                    $set('amount', number_format($this->totalAvailable, 2));
                                    
                                    Notification::make()
                                        ->title('Amount Adjusted')
                                        ->warning()
                                        ->body("Amount adjusted to maximum available: GHS " . number_format($this->totalAvailable, 2))
                                        ->send();
                                }
                            }),
                            
                        TextInput::make('msisdn')
                            ->label('Mobile Number')
                            ->required()
                            ->tel()
                            ->placeholder('e.g., 233574321997')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $set) {
                                if ($state && strlen($state) >= 10) {
                                    $this->verifyMobileNumber($state, $set);
                                }
                            }),
                            
                        Select::make('network')
                            ->label('Network')
                            ->required()
                            ->options([
                                'MTN' => 'MTN',
                                'VODAFONE' => 'Vodafone',
                                'ARTLTIGO' => 'AirtelTigo',
                            ])
                            ->live()
                            ->afterStateUpdated(function ($state, $get, $set) {
                                $msisdn = $get('msisdn');
                                if ($msisdn && $state) {
                                    $this->verifyMobileNumber($msisdn, $set);
                                }
                            }),
                            
                        TextInput::make('customer_name')
                            ->label('Account Holder Name')
                            ->readonly()
                            ->placeholder('Will be auto-populated after verification'),
                            
                        TextInput::make('narration')
                            ->label('Transaction Description')
                            ->default('Admin Fee Withdrawal')
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function verifyMobileNumber(string $msisdn, $set): void
    {
        try {
            $network = $this->data['network'] ?? 'MTN';
            
            // Format number for name enquiry (with leading 0, no 233)
            $formattedMsisdn = $this->formatMsisdnForEnquiry($msisdn);
            
            Log::info('Mobile number verification attempt', [
                'original_msisdn' => $msisdn,
                'formatted_msisdn' => $formattedMsisdn,
                'network' => $network,
                'endpoint' => 'https://admin.myeasydonate.com/api/v1/wallet/name-enquiry'
            ]);
            
            $payload = [
                'msisdn' => $formattedMsisdn,
                'network' => $network,
            ];
            
            Log::info('Sending name enquiry request', [
                'payload' => $payload
            ]);
            
            $response = Http::timeout(10)->post('https://admin.myeasydonate.com/api/v1/wallet/name-enquiry', $payload);
            
            Log::info('Name enquiry response received', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'successful' => $response->successful()
            ]);

            if ($response->successful()) {
                $data = $response->json();
                Log::info('Name enquiry response data', ['data' => $data]);
                
                // Check for nested structure first
                if (isset($data['success']) && $data['success'] && isset($data['data']['name'])) {
                    $customerName = $data['data']['name'];
                    $set('customer_name', $customerName);
                    
                    Log::info('Customer name found in nested structure', ['customerName' => $customerName]);
                    
                    Notification::make()
                        ->title('Number Verified')
                        ->success()
                        ->body("Account holder: {$customerName}")
                        ->send();
                } else if (isset($data['customerName'])) {
                    // Direct field
                    $set('customer_name', $data['customerName']);
                    
                    Log::info('Customer name found', ['customerName' => $data['customerName']]);
                    
                    Notification::make()
                        ->title('Number Verified')
                        ->success()
                        ->body("Account holder: {$data['customerName']}")
                        ->send();
                } else if (isset($data['customer_name'])) {
                    // Alternative field name
                    $set('customer_name', $data['customer_name']);
                    
                    Log::info('Customer name found (alternative field)', ['customer_name' => $data['customer_name']]);
                    
                    Notification::make()
                        ->title('Number Verified')
                        ->success()
                        ->body("Account holder: {$data['customer_name']}")
                        ->send();
                } else {
                    $set('customer_name', '');
                    
                    Log::warning('Customer name not found in response', [
                        'response_keys' => array_keys($data),
                        'full_response' => $data
                    ]);
                    
                    Notification::make()
                        ->title('Verification Failed')
                        ->warning()
                        ->body('Could not verify the mobile number. Response: ' . json_encode($data))
                        ->send();
                }
            } else {
                Log::error('Name enquiry API request failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);
                
                Notification::make()
                    ->title('Verification Failed')
                    ->danger()
                    ->body("API request failed. Status: {$response->status()}")
                    ->send();
            }
        } catch (\Exception $e) {
            Log::error('Mobile number verification failed', [
                'msisdn' => $msisdn,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            Notification::make()
                ->title('Verification Error')
                ->danger()
                ->body('Unable to verify mobile number: ' . $e->getMessage())
                ->send();
        }
    }

    /**
     * Format MSISDN for name enquiry (with leading 0, no 233)
     */
    private function formatMsisdnForEnquiry(string $msisdn): string
    {
        // Remove any spaces or special characters
        $msisdn = preg_replace('/[^0-9]/', '', $msisdn);
        
        // If starts with 233, replace with 0
        if (str_starts_with($msisdn, '233')) {
            return '0' . substr($msisdn, 3);
        }
        
        // If doesn't start with 0, add it
        if (!str_starts_with($msisdn, '0')) {
            return '0' . $msisdn;
        }
        
        return $msisdn;
    }

    /**
     * Format MSISDN for credit wallet (no leading 0, no 233)
     */
    private function formatMsisdnForCredit(string $msisdn): string
    {
        // Remove any spaces or special characters
        $msisdn = preg_replace('/[^0-9]/', '', $msisdn);
        
        // If starts with 233, remove it
        if (str_starts_with($msisdn, '233')) {
            return substr($msisdn, 3);
        }
        
        // If starts with 0, remove it
        if (str_starts_with($msisdn, '0')) {
            return substr($msisdn, 1);
        }
        
        return $msisdn;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('withdraw')
                ->label('Process Withdrawal')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Confirm Withdrawal')
                ->modalDescription(fn () => "Are you sure you want to withdraw GHS " . ($this->data['amount'] ?? '0') . "? This action cannot be undone.")
                ->modalSubmitActionLabel('Yes, Withdraw Now')
                ->action('processWithdrawal')
                ->disabled(fn () => $this->totalAvailable <= 0)
        ];
    }

    public function processWithdrawal(): void
    {
        try {
            $formData = $this->form->getState();
            
            // Validate required fields
            if (empty($formData['customer_name'])) {
                Notification::make()
                    ->title('Verification Required')
                    ->danger()
                    ->body('Please verify the mobile number first.')
                    ->send();
                return;
            }

            DB::beginTransaction();

            // Format MSISDN for credit wallet (no leading 0)
            $creditMsisdn = $this->formatMsisdnForCredit($formData['msisdn']);
            
            $creditPayload = [
                'customer' => $formData['customer_name'],
                'msisdn' => $creditMsisdn,
                'amount' => $formData['amount'],
                'network' => $formData['network'],
                'narration' => $formData['narration'],
            ];
            
            Log::info('Processing credit wallet request', [
                'original_msisdn' => $formData['msisdn'],
                'formatted_msisdn' => $creditMsisdn,
                'payload' => $creditPayload,
                'endpoint' => 'https://admin.myeasydonate.com/api/v1/payments/credit-wallet'
            ]);

            // Process mobile money payment
            $response = Http::timeout(30)->post('https://admin.myeasydonate.com/api/v1/payments/credit-wallet', $creditPayload);
            
            Log::info('Credit wallet response received', [
                'status' => $response->status(),
                'headers' => $response->headers(),
                'body' => $response->body(),
                'successful' => $response->successful()
            ]);

            if ($response->successful()) {
                // Handle partial withdrawal - only update fees up to the withdrawn amount
                $withdrawnAmount = (float) $formData['amount'];
                $runningTotal = 0;
                $updatedCount = 0;
                
                $fees = WithdrawalFee::where('status', 'calculated')
                    ->orderBy('created_at', 'asc')
                    ->get();
                
                foreach ($fees as $fee) {
                    if ($runningTotal + $fee->fee_amount <= $withdrawnAmount) {
                        // Update this fee to applied
                        $fee->update([
                            'status' => 'applied',
                            'metadata' => array_merge($fee->metadata ?? [], [
                                'admin_withdrawal' => [
                                    'processed_at' => now()->toISOString(),
                                    'msisdn' => $formData['msisdn'],
                                    'network' => $formData['network'],
                                    'customer_name' => $formData['customer_name'],
                                    'partial_amount' => $fee->fee_amount,
                                    'total_withdrawal' => $withdrawnAmount,
                                    'transaction_response' => $response->json()
                                ]
                            ])
                        ]);
                        
                        $runningTotal += $fee->fee_amount;
                        $updatedCount++;
                    } else if ($runningTotal < $withdrawnAmount) {
                        // Partial update for the last fee
                        $partialAmount = $withdrawnAmount - $runningTotal;
                        
                        // Create a new fee record for the remaining amount
                        $remainingFee = $fee->replicate();
                        $remainingFee->fee_amount = $fee->fee_amount - $partialAmount;
                        $remainingFee->status = 'calculated';
                        $remainingFee->save();
                        
                        // Update the current fee for the withdrawn portion
                        $fee->update([
                            'fee_amount' => $partialAmount,
                            'status' => 'applied',
                            'metadata' => array_merge($fee->metadata ?? [], [
                                'admin_withdrawal' => [
                                    'processed_at' => now()->toISOString(),
                                    'msisdn' => $formData['msisdn'],
                                    'network' => $formData['network'],
                                    'customer_name' => $formData['customer_name'],
                                    'partial_amount' => $partialAmount,
                                    'total_withdrawal' => $withdrawnAmount,
                                    'transaction_response' => $response->json()
                                ]
                            ])
                        ]);
                        
                        $updatedCount++;
                        break;
                    } else {
                        break;
                    }
                }

                DB::commit();

                // Log the successful withdrawal
                Log::info('Admin fee withdrawal processed', [
                    'amount' => $formData['amount'],
                    'msisdn' => $formData['msisdn'],
                    'network' => $formData['network'],
                    'fees_updated' => $updatedCount,
                    'response' => $response->json()
                ]);

                Notification::make()
                    ->title('Withdrawal Successful!')
                    ->success()
                    ->body("GHS {$formData['amount']} has been sent to {$formData['msisdn']}. {$updatedCount} fee records updated.")
                    ->send();

                // Refresh the page data
                $this->mount();
                
            } else {
                DB::rollBack();
                
                Log::error('Mobile money payment failed', [
                    'response' => $response->body(),
                    'status' => $response->status()
                ]);

                Notification::make()
                    ->title('Payment Failed')
                    ->danger()
                    ->body('Mobile money payment failed. Please try again.')
                    ->send();
            }

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('Admin withdrawal failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            Notification::make()
                ->title('Withdrawal Failed')
                ->danger()
                ->body('An error occurred during withdrawal: ' . $e->getMessage())
                ->send();
        }
    }
}
