<?php

namespace App\Services\Concerns;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

trait ConfiguresHttpClient
{
    /**
     * Create a pre-configured HTTP request client that handles SSL and User-Agent.
     */
    protected function client(int $timeout = 30): PendingRequest
    {
        $caPath = $this->resolveCaBundle();

        $request = Http::timeout($timeout)
            ->withUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');

        if ($caPath !== null) {
            $request->withOptions(['verify' => $caPath]);
        }

        return $request;
    }

    /**
     * Resolve a valid CA bundle path to prevent cURL error 77 on Windows/Laragon.
     */
    protected function resolveCaBundle(): string|bool|null
    {
        $iniCa = ini_get('curl.cainfo');
        if (! empty($iniCa) && file_exists($iniCa)) {
            return null; // System/php.ini configuration is valid
        }

        // Common Windows Laragon paths
        $candidates = [
            'C:/laragon/etc/ssl/cacert.pem',
            'C:/laragon/bin/php/cacert.pem',
            'C:/laragon/etc/apps/phpmyadmin6/vendor/composer/ca-bundle/res/cacert.pem',
            base_path('vendor/composer/ca-bundle/res/cacert.pem'),
        ];

        foreach ($candidates as $candidate) {
            if (file_exists($candidate)) {
                return $candidate;
            }
        }

        // Fallback: disable verify if no CA file exists to avoid fatal cURL 77 error
        return false;
    }
}

