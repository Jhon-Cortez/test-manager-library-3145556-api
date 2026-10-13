<?php

declare(strict_types=1);

namespace App\Adapters\Outbound\Persistence\Models;

use Illuminate\Database\Eloquent\Model;

final class BookModel extends Model
{
    protected $table = 'library.book';

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}
