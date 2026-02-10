<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineNotifyService
{
    protected string $apiUrl = 'https://notify-api.line.me/api/notify';
    protected ?string $token;

    public function __construct()
    {
        $this->token = config('services.line.notify_token');
    }

    /**
     * Send a message to LINE Notify.
     *
     * @param string $message
     * @return bool
     */
    public function send(string $message): bool
    {
        if (!$this->token) {
            // Log::warning('LINE Notify token not configured.');
            return false;
        }

        try {
            $response = Http::withToken($this->token)
                ->asForm()
                ->post($this->apiUrl, [
                    'message' => $message,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('LINE Notify failed: ' . $response->body());
            return false;
        } catch (\Exception $e) {
            Log::error('LINE Notify exception: ' . $e->getMessage());
            return false;
        }
    }
}
