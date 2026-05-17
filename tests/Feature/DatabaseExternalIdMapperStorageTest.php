<?php

declare(strict_types=1);

use AlturaCode\Billing\Core\Provider\ExternalIdMappingConflictException;
use AlturaCode\Billing\Laravel\DatabaseExternalIdMapperStorage;
use Illuminate\Support\Facades\DB;

it('upserts an existing internal id mapping', function () {
    $mapper = new DatabaseExternalIdMapperStorage();

    $mapper->store('customer', 'stripe', 'user_1', 'cus_1');
    $mapper->store('customer', 'stripe', 'user_1', 'cus_2');

    expect(DB::table('external_id_maps')->count())->toBe(1)
        ->and($mapper->getExternalId('customer', 'stripe', 'user_1'))->toBe('cus_2')
        ->and($mapper->getInternalId('customer', 'stripe', 'cus_1'))->toBeNull()
        ->and($mapper->getInternalId('customer', 'stripe', 'cus_2'))->toBe('user_1');
});

it('throws a typed exception when an external id belongs to another internal id', function () {
    $mapper = new DatabaseExternalIdMapperStorage();

    $mapper->store('customer', 'stripe', 'user_1', 'cus_1');

    expect(fn() => $mapper->store('customer', 'stripe', 'user_2', 'cus_1'))
        ->toThrow(ExternalIdMappingConflictException::class);
});

it('rolls back bulk upserts on conflicts', function () {
    $mapper = new DatabaseExternalIdMapperStorage();

    $mapper->store('customer', 'stripe', 'user_1', 'cus_1');

    expect(fn() => $mapper->storeMultiple([
        ['type' => 'customer', 'provider' => 'stripe', 'internalId' => 'user_2', 'externalId' => 'cus_2'],
        ['type' => 'customer', 'provider' => 'stripe', 'internalId' => 'user_3', 'externalId' => 'cus_1'],
    ]))->toThrow(ExternalIdMappingConflictException::class);

    expect(DB::table('external_id_maps')->count())->toBe(1)
        ->and($mapper->getExternalId('customer', 'stripe', 'user_2'))->toBeNull()
        ->and($mapper->getExternalId('customer', 'stripe', 'user_1'))->toBe('cus_1');
});

it('forgets mappings by internal id', function () {
    $mapper = new DatabaseExternalIdMapperStorage();

    $mapper->storeMultiple([
        ['type' => 'customer', 'provider' => 'stripe', 'internalId' => 'user_1', 'externalId' => 'cus_1'],
        ['type' => 'customer', 'provider' => 'stripe', 'internalId' => 'user_2', 'externalId' => 'cus_2'],
    ]);

    $mapper->forget('customer', 'stripe', 'user_1');
    $mapper->forget('customer', 'stripe', 'missing');

    expect($mapper->getExternalId('customer', 'stripe', 'user_1'))->toBeNull()
        ->and($mapper->getInternalId('customer', 'stripe', 'cus_1'))->toBeNull()
        ->and($mapper->getExternalId('customer', 'stripe', 'user_2'))->toBe('cus_2');
});

it('forgets multiple mappings in one transaction', function () {
    $mapper = new DatabaseExternalIdMapperStorage();

    $mapper->storeMultiple([
        ['type' => 'customer', 'provider' => 'stripe', 'internalId' => 'user_1', 'externalId' => 'cus_1'],
        ['type' => 'customer', 'provider' => 'stripe', 'internalId' => 'user_2', 'externalId' => 'cus_2'],
    ]);

    expect(fn() => $mapper->forgetMultiple([
        ['type' => 'customer', 'provider' => 'stripe', 'internalId' => 'user_1'],
        ['type' => 'customer', 'provider' => 'stripe'],
    ]))->toThrow(InvalidArgumentException::class);

    expect($mapper->getExternalId('customer', 'stripe', 'user_1'))->toBe('cus_1')
        ->and($mapper->getExternalId('customer', 'stripe', 'user_2'))->toBe('cus_2');

    $mapper->forgetMultiple([
        ['type' => 'customer', 'provider' => 'stripe', 'internalId' => 'user_1'],
        ['type' => 'customer', 'provider' => 'stripe', 'internalId' => 'user_2'],
    ]);

    expect(DB::table('external_id_maps')->count())->toBe(0);
});
