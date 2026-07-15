<?php

declare(strict_types=1);

namespace Models;

use Core\Model;

final class NowPaymentsTransaction extends Model
{
    public static $_table = DBprefix.'nowpayments_transactions';
}
