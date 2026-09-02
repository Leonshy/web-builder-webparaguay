<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = ['name', 'billing_email', 'credit_balance'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /** MVP: una organización = un usuario. La capa es invisible pero existe. */
    public function owner(): ?User
    {
        return $this->users()->oldest('id')->first();
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function aiUsages(): HasMany
    {
        return $this->hasMany(AiUsage::class);
    }
}
