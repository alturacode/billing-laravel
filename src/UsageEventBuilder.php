<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\Common\BillableIdentity;
use AlturaCode\Billing\Core\Common\FeatureKey;
use AlturaCode\Billing\Core\Features\UsageEvent;
use AlturaCode\Billing\Core\Features\UsageEventId;
use AlturaCode\Billing\Core\Features\UsageLedger;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

final class UsageEventBuilder
{
    private UsageEventId $id;

    private int $amount = 1;

    private DateTimeImmutable $recordedAt;

    /**
     * @var array<string, mixed>
     */
    private array $metadata = [];

    public function __construct(
        private readonly UsageLedger $ledger,
        private readonly string $billableType,
        private readonly string|int $billableId,
        private readonly string $featureKey,
    ) {
        $this->id = UsageEventId::generate();
        $this->recordedAt = new DateTimeImmutable('now', new DateTimeZone('UTC'));
    }

    public function withId(string|UsageEventId $id): self
    {
        $this->id = $id instanceof UsageEventId ? $id : UsageEventId::fromString($id);

        return $this;
    }

    public function withAmount(int $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    public function withMetadata(array $metadata): self
    {
        $this->metadata = $metadata;

        return $this;
    }

    public function withRecordedAt(DateTimeInterface $recordedAt): self
    {
        $this->recordedAt = DateTimeImmutable::createFromInterface($recordedAt)
            ->setTimezone(new DateTimeZone('UTC'));

        return $this;
    }

    public function record(): bool
    {
        return $this->ledger->record(UsageEvent::create(
            $this->id,
            BillableIdentity::fromString($this->billableType, $this->billableId),
            FeatureKey::fromString($this->featureKey),
            $this->amount,
            $this->recordedAt,
            $this->metadata,
        ));
    }
}
