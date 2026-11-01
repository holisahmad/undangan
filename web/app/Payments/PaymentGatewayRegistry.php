<?php

declare(strict_types=1);

namespace App\Payments;

use App\Contracts\Payments\PaymentGateway;
use App\Exceptions\Payments\UnknownPaymentProvider;
use Illuminate\Contracts\Container\Container;

/**
 * Resolves gateway adapters from configuration so no call site hardcodes a
 * provider class name. Availability is checked here rather than at the call
 * sites, because a webhook endpoint must not be able to forget the check.
 */
final class PaymentGatewayRegistry
{
    public function __construct(private readonly Container $container) {}

    /**
     * @throws UnknownPaymentProvider
     */
    public function resolve(string $provider): PaymentGateway
    {
        $class = config("platform.payments.gateways.{$provider}");

        if (! is_string($class) || ! class_exists($class)) {
            throw UnknownPaymentProvider::named($provider);
        }

        $gateway = $this->container->make($class);

        if (! $gateway instanceof PaymentGateway) {
            throw UnknownPaymentProvider::named($provider);
        }

        // An unavailable provider is reported as unknown: telling a caller that
        // the sandbox exists but is switched off would advertise the endpoint.
        if (! $gateway->isEnabled()) {
            throw UnknownPaymentProvider::named($provider);
        }

        return $gateway;
    }

    public function defaultGateway(): PaymentGateway
    {
        // The manual transfer provider is intentionally not a gateway adapter
        // (docs/adr/011-manual-bank-transfer-payments.md), so it never reaches
        // this method; callers branch on BankTransfer::isDefault() first.
        return $this->resolve((string) config('platform.payments.default_gateway', FakePaymentGateway::PROVIDER));
    }

    /**
     * Whether a provider can currently be used at all. Screens and commands that
     * merely want to know that ask here, so they cannot drift away from the
     * switches the gateway itself consults.
     */
    public function available(string $provider): bool
    {
        try {
            $this->resolve($provider);
        } catch (UnknownPaymentProvider) {
            return false;
        }

        return true;
    }
}
