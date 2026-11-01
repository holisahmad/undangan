<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Payments\ProcessPaymentNotification;
use App\Exceptions\Payments\InvalidPaymentPayload;
use App\Exceptions\Payments\InvalidPaymentSignature;
use App\Exceptions\Payments\UnknownPaymentProvider;
use App\Payments\PaymentGatewayRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;

/**
 * Public webhook endpoint. It answers with machine-readable status codes and
 * never redirects or renders a page
 * (docs/PAYMENT_FLOW.md, sections 3 and 4).
 */
final class PaymentWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        string $provider,
        PaymentGatewayRegistry $gateways,
        ProcessPaymentNotification $notifications,
    ): JsonResponse {
        try {
            $gateway = $gateways->resolve($provider);
        } catch (UnknownPaymentProvider) {
            return response()->json(['status' => 'unknown_provider'], 404);
        }

        try {
            $event = $notifications->handle(
                provider: $provider,
                rawPayload: $request->getContent(),
                signature: $request->header($gateway->signatureHeader()),
                payload: $this->decode($request->getContent()),
            );
        } catch (InvalidPaymentSignature) {
            return response()->json(['status' => 'rejected', 'reason' => 'invalid_signature'], 403);
        } catch (InvalidPaymentPayload|InvalidArgumentException) {
            return response()->json(['status' => 'rejected', 'reason' => 'invalid_payload'], 400);
        }

        return response()->json([
            'status' => 'accepted',
            'processing_status' => $event->processing_status->value,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $raw): array
    {
        $decoded = json_decode($raw, true);

        if (! is_array($decoded)) {
            throw InvalidPaymentPayload::reason('Body webhook bukan JSON yang valid.');
        }

        return $decoded;
    }
}
