<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Laravel\Exceptions\NoRedirectRequiredException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

/** @mixin \AlturaCode\Billing\Core\Provider\BillingProviderResult */
final readonly class BillingProviderResult
{
    public function __construct(
        public \AlturaCode\Billing\Core\Provider\BillingProviderResult $result,
        public Subscription                                            $subscription
    )
    {
    }

    public function __call(string $name, array $arguments): mixed
    {
        return $this->result->{$name}(...$arguments);
    }

    public function redirect(): RedirectResponse
    {
        if ($this->result->clientAction->type->isRedirect()) {
            return Redirect::to($this->result->clientAction->url);
        }

        throw new NoRedirectRequiredException(
            'This billing provider result does not require a redirect action.'
        );
    }
}