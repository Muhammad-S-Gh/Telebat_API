<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;

interface NotificationRepositoryInterface
{
    public function allForUser(User $user): Collection;

    public function unreadForUser(User $user): Collection;

    public function unreadCountForUser(User $user): int;

    public function findForUser(User $user, string $id): DatabaseNotification;

    public function markAllAsRead(User $user): void;
}
