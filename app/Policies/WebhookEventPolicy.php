<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Models\WebhookEvent;

/**
 * Eventos recebidos dos gateways são só para consulta.
 */
final class WebhookEventPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, WebhookEvent $event): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, WebhookEvent $event): bool
    {
        return false;
    }

    public function delete(User $user, WebhookEvent $event): bool
    {
        return false;
    }
}
