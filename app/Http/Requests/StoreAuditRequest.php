<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAuditRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'url' => ['required', 'string', 'max:2048', 'url:http,https'],
        ];
    }

    /**
     * Additional validation after standard rules pass.
     */
    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator) {
            $url = $this->input('url');

            if (! is_string($url) || ! filter_var($url, FILTER_VALIDATE_URL)) {
                return;
            }

            $host = parse_url($url, PHP_URL_HOST);

            if (! is_string($host) || $host === '') {
                $validator->errors()->add('url', 'The URL must contain a valid hostname.');

                return;
            }

            // Block private/reserved IPs to prevent SSRF
            $ip = gethostbyname($host);
            if ($ip !== $host && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                $validator->errors()->add('url', 'The URL must not point to a private or reserved IP address.');
            }
        });
    }

    /**
     * Extract the domain from the validated URL.
     */
    public function domain(): string
    {
        $host = parse_url($this->validated('url'), PHP_URL_HOST);

        return is_string($host) ? $host : '';
    }
}

