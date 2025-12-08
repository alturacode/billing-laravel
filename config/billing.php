<?php

use AlturaCode\Billing\Core\Provider\SynchronousBillingProvider;
use AlturaCode\Billing\Laravel\ConfigFeatureRepository;
use AlturaCode\Billing\Laravel\ConfigProductRepository;
use AlturaCode\Billing\Laravel\EloquentSubscriptionRepository;
use AlturaCode\Billing\Laravel\Subscription;

return [
    'provider' => 'sync',

    'providers' => [
        'sync' => SynchronousBillingProvider::class,
    ],

    'features' => [
        [
            'key' => 'premium_support',
            'name' => 'Premium Support',
            'description' => 'Access to premium support.',
            'kind' => 'flag',
        ],
        [
            'key' => 'users',
            'name' => 'Team Members',
            'description' => 'The maximum number of team members allowed.',
            'kind' => 'limit',
            'unit' => 'user',
        ],
    ],

    'plans' => [
        [
            'id' => '01KBZ5QVFC48JBW3P9V61CKMF1',
            'kind' => 'plan',
            'name' => 'Free',
            'slug' => 'free',
            'prices' => [],
            'features' => [
                ['key' => 'users', 'value' => 3],
            ],
        ],
    ],

    'addons' => [
        [
            'id' => '01KBZ5Q3WG725KV8ZH1Z6Y6HP6',
            'name' => 'Premium Support',
            'slug' => 'premium_support',
            'prices' => [
                [
                    'id' => '01KBZ5R52MW2W6DY91FC8BEYK5',
                    'price' => ['amount' => 1000, 'currency' => 'USD'],
                    'interval' => ['type' => 'month', 'count' => 1],
                ],
                'features' => [
                    ['key' => 'premium_support', 'value' => true],
                ],
            ],
        ],
    ],

    'models' => [
        'subscription' => Subscription::class,
    ],

    'repositories' => [
        'features' => ConfigFeatureRepository::class,
        'products' => ConfigProductRepository::class,
        'subscriptions' => EloquentSubscriptionRepository::class,
    ],
];