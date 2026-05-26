<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreateLinkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|\Illuminate\Validation\Rules\In>>
     */
    public function rules(): array
    {
        return [
            'original_url' => [
                'required',
                'string',
                'max:2048',
                'url:http,https',
            ],
            'expires_at' => [
                'nullable',
                'date',
                'after:now',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'original_url.required' => 'Please paste a URL to shorten.',
            'original_url.url'      => 'That doesn\'t look like a valid http(s) URL.',
            'original_url.max'      => 'URLs must be 2,048 characters or fewer.',
            'expires_at.date'       => 'The expiry must be a valid date and time.',
            'expires_at.after'      => 'The expiry must be in the future.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'original_url' => 'destination URL',
            'expires_at'   => 'expiry',
        ];
    }
}
