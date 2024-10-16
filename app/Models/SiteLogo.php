<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SiteLogo extends Model
{
    use HasFactory;

    protected $fillable = ['image', 'agent_id'];

    protected $appends = ['img_url'];

    public function getImgUrlAttribute()
    {
        return asset('assets/img/sitelogo/'.$this->image);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }
}
