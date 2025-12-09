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
            'key' => 'storage_space',
            'name' => 'Storage Space',
            'description' => 'Available storage space in GB',
            'kind' => 'limit',
            'unit' => 'GB'
        ],
        [
            'key' => 'users',
            'name' => 'Team Members',
            'description' => 'Number of team member seats',
            'kind' => 'limit',
            'unit' => 'user'
        ],
        [
            'key' => 'projects',
            'name' => 'Projects',
            'description' => 'Number of active projects allowed',
            'kind' => 'limit',
            'unit' => 'project'
        ],
        [
            'key' => 'priority_support',
            'name' => 'Priority Support',
            'description' => 'Get priority email and chat support',
            'kind' => 'flag'
        ],
        [
            'key' => 'custom_domain',
            'name' => 'Custom Domain',
            'description' => 'Use your own custom domain',
            'kind' => 'flag'
        ]
    ],

    'plans' => [
        [
            'id' => '01KBZ5QVFC48JBW3P9V61CKMF1',
            'name' => 'Free',
            'description' => 'Perfect for getting started',
            'slug' => 'free',
            'prices' => [
                [
                    'id' => '01KC0PVCBTXR73W2XDZZ2R7F05',
                    'price' => ['amount' => 0, 'currency' => 'usd'],
                    'interval' => ['type' => 'month', 'count' => 1],
                ]
            ],
            'features' => [
                ['key' => 'storage_space', 'value' => 5],
                ['key' => 'users', 'value' => 2],
                ['key' => 'projects', 'value' => 3]
            ],
        ],
        [
            'id' => '01KBZ5QVFC48JBW3P9V61CKMF2',
            'name' => 'Pro',
            'description' => 'Best for growing teams',
            'slug' => 'pro',
            'prices' => [
                [
                    'id' => '01KBZ5R52MW2W6DY91FC8BEYK1',
                    'price' => ['amount' => 1500, 'currency' => 'usd'],
                    'interval' => ['type' => 'month', 'count' => 1],
                ],
                [
                    'id' => '01KBZ5R52MW2W6DY91FC8BEYK2',
                    'price' => ['amount' => 15000, 'currency' => 'usd'],
                    'interval' => ['type' => 'year', 'count' => 1],
                ]
            ],
            'features' => [
                ['key' => 'storage_space', 'value' => 50],
                ['key' => 'users', 'value' => 10],
                ['key' => 'projects', 'value' => 20],
                ['key' => 'priority_support', 'value' => true]
            ],
        ],
        [
            'id' => '01KBZ5QVFC48JBW3P9V61CKMF3',
            'name' => 'Enterprise',
            'description' => 'For large organizations',
            'slug' => 'enterprise',
            'prices' => [
                [
                    'id' => '01KBZ5R52MW2W6DY91FC8BEYK3',
                    'price' => ['amount' => 5000, 'currency' => 'usd'],
                    'interval' => ['type' => 'month', 'count' => 1],
                ],
                [
                    'id' => '01KBZ5R52MW2W6DY91FC8BEYK4',
                    'price' => ['amount' => 50000, 'currency' => 'usd'],
                    'interval' => ['type' => 'year', 'count' => 1],
                ]
            ],
            'features' => [
                ['key' => 'storage_space', 'value' => 500],
                ['key' => 'users', 'value' => 50],
                ['key' => 'projects', 'value' => 'unlimited'],
                ['key' => 'priority_support', 'value' => true],
                ['key' => 'custom_domain', 'value' => true]
            ],
        ]
    ],

    'addons' => [
        [
            'id' => '01KBZ5Q3WG725KV8ZH1Z6Y6HP1',
            'name' => 'Extra Storage',
            'description' => 'Additional storage space',
            'slug' => 'extra_storage',
            'prices' => [
                [
                    'id' => '01KBZ5R52MW2W6DY91FC8BEYK5',
                    'price' => ['amount' => 1000, 'currency' => 'usd'],
                    'interval' => ['type' => 'month', 'count' => 1],
                ]
            ],
            'features' => [
                ['key' => 'storage_space', 'value' => 100],
            ],
        ],
        [
            'id' => '01KBZ5Q3WG725KV8ZH1Z6Y6HP2',
            'name' => 'Additional User Seats',
            'description' => 'Add more team members',
            'slug' => 'extra_users',
            'prices' => [
                [
                    'id' => '01KBZ5R52MW2W6DY91FC8BEYK6',
                    'price' => ['amount' => 800, 'currency' => 'usd'],
                    'interval' => ['type' => 'month', 'count' => 1],
                ]
            ],
            'features' => [
                ['key' => 'users', 'value' => 5],
            ],
        ]
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