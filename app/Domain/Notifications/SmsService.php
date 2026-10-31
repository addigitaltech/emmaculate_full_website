<?php

namespace App\Domain\Notifications;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Text messages through Termii (a Nigerian SMS provider). Off until SMS_DRIVER=termii and the Termii keys are set in
 * the host's private settings. Failures are logged and never stop a payment from being recorded.
 */
final class SmsService
{
    public static function enabled(): bool
    {
        return config('services.sms.driver') === 'termii'
            && filled(config('services.sms.termii.api_key'))
            && filled(config('services.sms.termii.sender_id'));
    }

    /** Turn 0803 123 4567 or +234 803 123 4567 into 2348031234567; null when it is not a Nigerian mobile number. */
    public static function normalise(string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', $number) ?? '';
        if (preg_match('/^0\d{10}$/', $digits)) {
            $digits = '234'.substr($digits, 1);
        }

        return preg_match('/^234\d{10}$/', $digits) ? $digits : null;
    }

    public function send(string $number, string $message): bool
    {
        if (! self::enabled()) {
            return false;
        }
        $to = self::normalise($number);
        if ($to === null) {
            return false;
        }

        try {
            $response = Http::timeout(10)->asJson()->post(rtrim((string) config('services.sms.termii.base_url'), '/').'/api/sms/send', [
                'api_key' => config('services.sms.termii.api_key'),
                'to' => $to,
                'from' => config('services.sms.termii.sender_id'),
                'sms' => mb_substr(preg_replace('/[\x00-\x1F\x7F]+/', ' ', $message) ?? '', 0, 300),
                'type' => 'plain',
                'channel' => config('services.sms.termii.channel', 'generic'),
            ]);
            if (! $response->successful()) {
                Log::warning('SMS was not accepted by the provider.', ['status' => $response->status()]);

                return false;
            }

            return true;
        } catch (\Throwable $exception) {
            Log::warning('SMS could not be sent: '.$exception->getMessage());

            return false;
        }
    }
}
