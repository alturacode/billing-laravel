<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\BillableDetailsResolver;
use AlturaCode\Billing\Core\Common\BillableDetails;
use AlturaCode\Billing\Core\Common\BillableIdentity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use LogicException;

final readonly class DefaultBillableDetailsResolver implements BillableDetailsResolver
{
    public function resolve(BillableIdentity $billable): ?BillableDetails
    {
        $class = Relation::getMorphedModel($billable->type());

        /** @var Model|null $model */
        $model = $class::find($billable->id());

        if (null === $model) {
            return null;
        }

        if (!($model instanceof Billable)) {
            throw new LogicException('The billable model must implement the Billable interface.');
        }

        return $model->resolveBillableDetails();
    }
}