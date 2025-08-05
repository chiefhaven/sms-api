<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Client;
/**
 * Class StoreSMSRequest
 *
 * @package App\Http\Requests
 */

class StoreSMSRequest extends FormRequest
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
    public function prepareForValidation()
    {
        if ($this->has('to') && str_starts_with($this->input('to'), '0')) {
            $this->merge([
                'to' => '+265' . substr($this->input('to'), 1),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'to' => ['required', 'regex:/^\+?[1-9]\d{7,14}$/'],
            'message' => 'required|string|max:160',
            'from' => [
                'required',
                'string',
                'max:15',
                //Rule::in(Client::pluck('sender_id')->toArray()),
            ],
        ];
    }

}
