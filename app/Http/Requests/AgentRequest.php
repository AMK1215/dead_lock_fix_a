<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AgentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'user_name' => 'required', 'string', 'unique:users,user_name',
            'name' => 'required|min:3|string',
            'phone' => ['required', 'regex:/^[0-9]+$/'],
            'password' => 'required|min:6',
            'amount' => 'nullable|numeric',
            'referral_code' => ['required', 'string', 'unique:users,referral_code'],
            'agent_logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'payment_type_id' => 'required|numeric|exists:payment_types,id',
            'account_name' => 'required|string',
            'account_number' => ['required', 'regex:/^[0-9]+$/'],
            'line_id' => 'nullable',
            'commission' => 'sometimes', 'integer',
        ];
    }
}
