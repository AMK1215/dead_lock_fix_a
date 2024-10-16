<?php

namespace App\Models;

use App\Models\Admin\Bank;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use function Ramsey\Uuid\v1;

class DepositRequest extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'agent_id', 'agent_payment_type_id', 'amount', 'status', 'refrence_no'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function paymentType(): BelongsTo
    {
        return $this->belongsTo(PaymentType::class, 'agent_payment_type_id');
    }
}
