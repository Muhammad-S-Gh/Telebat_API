<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Notifications\DatabaseNotification;

class EloquentNotificationRepository implements NotificationRepositoryInterface
{
    public function allForUser(User $user): Collection
    {
        return $user->notifications()->get();
    }

    public function unreadForUser(User $user): Collection
    {
        return $user->unreadNotifications()->get();
    }

    public function unreadCountForUser(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function findForUser(User $user, string $id): DatabaseNotification
    {
        return $user->notifications()->findOrFail($id);
    }

    public function markAllAsRead(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }
}
