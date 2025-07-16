<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BoostCampaignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization is handled in the controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'plan_id' => [
                'required',
                'exists:boost_plans,id',
                function ($attribute, $value, $fail) {
                    $plan = \App\Models\BoostPlan::find($value);
                    if ($plan && $plan->status !== 'active') {
                        $fail('The selected boost plan is not available.');
                    }
                }
            ],
            'payment_method_id' => [
                'required',
                'exists:payment_methods,id',
                function ($attribute, $value, $fail) {
                    $paymentMethod = \App\Models\PaymentMethod::find($value);
                    if ($paymentMethod && $paymentMethod->status !== 'active') {
                        $fail('The selected payment method is not available.');
                    }
                }
            ],
        ];
    }
}
