<?php

namespace App\Services;

use App\Models\SmsLog;
use App\Models\User;
use App\Support\PhoneNumber;
use App\Support\SmsTemplates;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

class SmsService
{
    public function isConfigured(): bool
    {
        $apiKey = trim((string) config('services.httpsms.api_key', ''));
        $from = trim((string) config('services.httpsms.from', ''));

        return $apiKey !== '' && $from !== '';
    }

    /**
     * @return array{ok: bool, status: string, error: ?string}
     */
    public function send(
        string $phone,
        string $message,
        string $purpose,
        ?User $user = null,
        ?Model $related = null
    ): array {
        if (!PhoneNumber::isSendable($phone)) {
            $this->log($phone, $purpose, 'skipped', null, 'invalid_or_placeholder', $user, $related);

            return [
                'ok'     => false,
                'status' => 'skipped',
                'error'  => 'invalid_or_placeholder',
            ];
        }

        if (!$this->isConfigured()) {
            $this->log($phone, $purpose, 'skipped', null, 'not_configured', $user, $related);

            return [
                'ok'     => false,
                'status' => 'skipped',
                'error'  => 'not_configured',
            ];
        }

        $normalizedTo = PhoneNumber::normalize($phone);
        $toE164 = PhoneNumber::toE164($normalizedTo);

        $rawFrom = trim((string) config('services.httpsms.from'));
        $normalizedFrom = PhoneNumber::normalize($rawFrom);
        $fromE164 = $normalizedFrom !== null ? PhoneNumber::toE164($normalizedFrom) : $rawFrom;

        $baseUrl = rtrim((string) config('services.httpsms.base_url', 'https://api.httpsms.com'), '/');
        $timeout = (int) config('services.httpsms.timeout', 10);
        $apiKey = (string) config('services.httpsms.api_key');

        $payload = [
            'from'    => $fromE164,
            'to'      => $toE164,
            'content' => SmsTemplates::sanitize($message),
        ];

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
            ])
                ->timeout($timeout)
                ->acceptJson()
                ->post($baseUrl . '/v1/messages/send', $payload);

            if ($response->successful()) {
                $providerId = $response->json('data.id') ?? $response->json('id');
                $this->log(
                    $normalizedTo,
                    $purpose,
                    'sent',
                    is_string($providerId) ? $providerId : null,
                    null,
                    $user,
                    $related
                );

                return [
                    'ok'     => true,
                    'status' => 'sent',
                    'error'  => null,
                ];
            }

            $errorCode = 'http_' . $response->status();
            $this->log($normalizedTo, $purpose, 'failed', null, $errorCode, $user, $related);

            return [
                'ok'     => false,
                'status' => 'failed',
                'error'  => $errorCode,
            ];
        } catch (ConnectionException $e) {
            $this->log($normalizedTo, $purpose, 'failed', null, 'ConnectionException', $user, $related);

            return [
                'ok'     => false,
                'status' => 'failed',
                'error'  => 'ConnectionException',
            ];
        } catch (RequestException $e) {
            $errorCode = $e->response ? ('http_' . $e->response->status()) : 'RequestException';
            $this->log($normalizedTo, $purpose, 'failed', null, $errorCode, $user, $related);

            return [
                'ok'     => false,
                'status' => 'failed',
                'error'  => $errorCode,
            ];
        } catch (\Throwable $e) {
            $shortClass = class_basename($e);
            $this->log($normalizedTo, $purpose, 'failed', null, substr($shortClass, 0, 60), $user, $related);

            return [
                'ok'     => false,
                'status' => 'failed',
                'error'  => substr($shortClass, 0, 60),
            ];
        }
    }

    public function sendAfterResponse(
        string $phone,
        string $message,
        string $purpose,
        ?User $user = null,
        ?Model $related = null
    ): void {
        $userId = $user?->getKey();
        $relatedClass = $related ? $related::class : null;
        $relatedId = $related?->getKey();

        dispatch(function () use ($phone, $message, $purpose, $userId, $relatedClass, $relatedId) {
            $reloadedUser = $userId ? User::find($userId) : null;
            $reloadedRelated = ($relatedClass && $relatedId && class_exists($relatedClass))
                ? $relatedClass::find($relatedId)
                : null;

            app(self::class)->send($phone, $message, $purpose, $reloadedUser, $reloadedRelated);
        })->afterResponse();
    }

    private function log(
        string $phone,
        string $purpose,
        string $status,
        ?string $providerMessageId,
        ?string $errorCode,
        ?User $user,
        ?Model $related
    ): void {
        try {
            SmsLog::create([
                'user_id'             => $user?->getKey(),
                'purpose'             => substr($purpose, 0, 30),
                'phone_masked'        => substr(PhoneNumber::mask($phone), 0, 20),
                'status'              => $status,
                'provider_message_id' => $providerMessageId,
                'error_code'          => $errorCode ? substr($errorCode, 0, 60) : null,
                'related_type'        => $related ? $related::class : null,
                'related_id'          => $related?->getKey(),
            ]);
        } catch (\Throwable) {
            // Never throw from SMS logging
        }
    }
}
