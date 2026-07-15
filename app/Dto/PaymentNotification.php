<?php

namespace App\Dto;

use Carbon\Carbon;

final readonly class PaymentNotification {

    public function __construct(
        public int $dossierId,
        public int $amount,
        public Carbon $paymentDate,
    ) {
    }

}
