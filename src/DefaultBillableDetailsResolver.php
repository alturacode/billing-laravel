<?php

declare(strict_types=1);

namespace AlturaCode\Billing\Laravel;

use AlturaCode\Billing\Core\BillableDetailsResolver;
use AlturaCode\Billing\Core\Common\BillableDetails;
use AlturaCode\Billing\Core\Common\BillableIdentity;
use Illuminate\Database\Eloquent\Relations\Relation;

final readonly class DefaultBillableDetailsResolver implements BillableDetailsResolver
{
    public function resolve(BillableIdentity $billable): ?BillableDetails
    {
        $class = Relation::getMorphedModel($billable->type());
        $model = $class::find($billable->id());

        if (!$model) {
            return null;
        }

        return $model->resolveBillableDetails($billable);
    }
}