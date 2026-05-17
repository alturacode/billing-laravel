<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\Provider\ExternalIdMappingConflictException;
use AlturaCode\Billing\Core\Provider\ExternalIdMapper;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class DatabaseExternalIdMapperStorage implements ExternalIdMapper
{
    public function store(string $type, string $provider, string|int $internalId, string|int $externalId): void
    {
        DB::transaction(function () use ($type, $provider, $internalId, $externalId) {
            $this->storeMapping($type, $provider, $internalId, $externalId);
        });
    }

    public function storeMultiple(array $data): void
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $item) {
                $this->assertStoreItem($item);
            }

            foreach ($data as $item) {
                $this->storeMapping($item['type'], $item['provider'], $item['internalId'], $item['externalId']);
            }
        });
    }

    public function forget(string $type, string $provider, string|int $internalId): void
    {
        DB::table('external_id_maps')->where([
            'type' => $type,
            'provider' => $provider,
            'internal_id' => $internalId,
        ])->delete();
    }

    public function forgetMultiple(array $data): void
    {
        DB::transaction(function () use ($data) {
            foreach ($data as $item) {
                $this->assertForgetItem($item);

                $this->forget($item['type'], $item['provider'], $item['internalId']);
            }
        });
    }

    public function getExternalId(string $type, string $provider, string|int $internalId): string|int|null
    {
        return DB::table('external_id_maps')->where([
            'type' => $type,
            'provider' => $provider,
            'internal_id' => $internalId
        ])->value('external_id');
    }

    public function getExternalIdMap(string $type, string $provider, array $internalIds): array
    {
        return DB::table('external_id_maps')->where([
            'type' => $type,
            'provider' => $provider,
        ])->whereIn('internal_id', $internalIds)->pluck('external_id', 'internal_id')->toArray();
    }

    public function getInternalId(string $type, string $provider, string|int $externalId): string|int|null
    {
        return DB::table('external_id_maps')->where([
            'type' => $type,
            'provider' => $provider,
            'external_id' => $externalId
        ])->value('internal_id');
    }

    public function getInternalIdMap(string $type, string $provider, array $externalIds): array
    {
        return DB::table('external_id_maps')->where([
            'type' => $type,
            'provider' => $provider,
        ])->whereIn('external_id', $externalIds)->pluck('internal_id', 'external_id')->toArray();
    }

    private function assertNoExternalConflict(
        string $type,
        string $provider,
        string|int $internalId,
        string|int $externalId,
    ): void
    {
        $existingInternalId = DB::table('external_id_maps')->where([
            'type' => $type,
            'provider' => $provider,
            'external_id' => $externalId,
        ])->value('internal_id');

        if ($existingInternalId !== null && (string) $existingInternalId !== (string) $internalId) {
            throw ExternalIdMappingConflictException::forExternalId(
                $type,
                $provider,
                $externalId,
                $existingInternalId,
                $internalId,
            );
        }
    }

    private function storeMapping(string $type, string $provider, string|int $internalId, string|int $externalId): void
    {
        $this->assertNoExternalConflict($type, $provider, $internalId, $externalId);

        try {
            DB::table('external_id_maps')->upsert(
                [[
                    'type' => $type,
                    'provider' => $provider,
                    'internal_id' => $internalId,
                    'external_id' => $externalId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]],
                ['type', 'provider', 'internal_id'],
                ['external_id', 'updated_at'],
            );
        } catch (QueryException $exception) {
            $this->assertNoExternalConflict($type, $provider, $internalId, $externalId);

            throw $exception;
        }
    }

    private function assertStoreItem(array $item): void
    {
        if (!isset($item['type'], $item['provider'], $item['internalId'], $item['externalId'])) {
            throw new InvalidArgumentException('Item in data array must contain type, provider, internalId, and externalId fields');
        }
    }

    private function assertForgetItem(array $item): void
    {
        if (!isset($item['type'], $item['provider'], $item['internalId'])) {
            throw new InvalidArgumentException('Item in data array must contain type, provider, and internalId fields');
        }
    }
}
