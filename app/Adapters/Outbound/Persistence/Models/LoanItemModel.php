<?php

declare(strict_types=1);

namespace App\Adapters\Outbound\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class LoanItemModel extends Model
{
    protected $table = 'library.loan_item';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
