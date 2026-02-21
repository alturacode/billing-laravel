<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\Provider\ExternalIdMapper;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class DatabaseExternalIdMapperStorage implements ExternalIdMapper
{
    public function store(string $type, string $provider, string|int $internalId, string|int $externalId): void
    {
        DB::table('external_id_maps')->insert([
            'type' => $type,
            'provider' => $provider,
            'internal_id' => $internalId,
            'external_id' => $externalId,
        ]);
    }

    public function storeMultiple(array $data): void
    {
        foreach ($data as $item) {
            if (!isset($item['type'], $item['provider'], $item['internalId'], $item['externalId'])) {
                throw new InvalidArgumentException('Item in data array must contain type, provider, internalId, and externalId fields');
            }
        }

        $transformedData = array_map(function ($item) {
            return [
                'type' => $item['type'],
                'provider' => $item['provider'],
                'internal_id' => $item['internalId'],
                'external_id' => $item['externalId'],
            ];
        }, $data);

        DB::table('external_id_maps')->insert($transformedData);
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
}