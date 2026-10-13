<?php

declare(strict_types=1);

namespace App\Adapters\Outbound\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class LoanModel extends Model
{
    protected $table = 'library.loan';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
