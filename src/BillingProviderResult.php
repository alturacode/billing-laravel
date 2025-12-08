<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Redirect;

/** @mixin \AlturaCode\Billing\Core\Provider\BillingProviderResult */
final readonly class BillingProviderResult
{
    public function __construct(
        public \AlturaCode\Billing\Core\Provider\BillingProviderResult $result
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

        throw new Exception(
            'Redirect action not supported by this billing provider.'
        );
    }
}