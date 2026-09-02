<?php

namespace App\Actions;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Alta de cuenta. En el MVP crea la organización y su único usuario juntos:
 * la capa `organization` es invisible en la interfaz pero SIEMPRE existe.
 * El día que una organización tenga varios usuarios, no hay que migrar nada.
 */
final class RegisterAccount
{
    public function handle(string $name, string $email, string $password): User
    {
        $organization = Organization::create([
            'name' => $name,
            'billing_email' => $email,
        ]);

        return $organization->users()->create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
        ]);
    }
}
