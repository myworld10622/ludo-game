<?php

namespace App\Services\Auth;

use App\Models\User;

class ExternalIdentitySyncService
{
    public function queueRegistrationSync(User $user): void
    {
        // Placeholder for future external identity sync integration.
    }

    public function queueLoginSync(User $user): void
    {
        // Placeholder for future external identity sync integration.
    }
}
