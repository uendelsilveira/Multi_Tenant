<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Dados que só cliente tem. Vive no banco do tenant.
 *
 * @property int $user_id
 * @property string|null $phone
 * @property string|null $document
 * @property string|null $notes
 */
final class CustomerProfile extends Model
{
    public $incrementing = false;

    protected $primaryKey = 'user_id';

    protected $fillable = [
        'user_id',
        'phone',
        'document',
        'notes',
    ];
}
