<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Organization extends Model
{
    protected $fillable = [
        'name', 'plan', 'billing_email', 'credit_balance',
        'billing_phone', 'billing_address', 'billing_city', 'billing_state',
        'billing_postcode', 'billing_country',
    ];

    public function billingComplete(): bool
    {
        return filled($this->billing_phone) && filled($this->billing_address)
            && filled($this->billing_city) && filled($this->billing_country);
    }

    /** @return array<string,string> para WHMCS AddClient */
    public function billingProfile(): array
    {
        return [
            'phone' => (string) $this->billing_phone,
            'address' => (string) $this->billing_address,
            'city' => (string) $this->billing_city,
            'state' => (string) ($this->billing_state ?: $this->billing_city),
            'postcode' => (string) ($this->billing_postcode ?: '0000'),
            'country' => (string) ($this->billing_country ?: 'PY'),
        ];
    }

    public function isFree(): bool
    {
        return $this->plan === 'free';
    }

    /** En el plan free: 1 proyecto que todavía no se publicó. */
    public function canStartNewProject(): bool
    {
        if (! $this->isFree()) {
            return true;
        }

        return $this->projects()->where('status', '!=', 'published')->count() === 0;
    }

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
