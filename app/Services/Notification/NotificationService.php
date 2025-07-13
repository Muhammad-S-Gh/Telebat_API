<?php

namespace App\Services\Notification;

use App\Models\User;
use App\Repositories\Contracts\NotificationRepositoryInterface;

class NotificationService
{
    public function __construct(private readonly NotificationRepositoryInterface $notifications) {}

    public function allFor(User $user)
    {
        return $this->notifications->allForUser($user);
    }

    public function markAsRead(User $user, string $id): void
    {
        $notification = $this->notifications->findForUser($user, $id);
        $notification->markAsRead();
    }

    public function unreadFor(User $user): array
    {
        return [
            'results' => $this->notifications->unreadCountForUser($user),
            'notifications' => $this->notifications->unreadForUser($user),
        ];
    }

    public function markAllAsRead(User $user): void
    {
        $this->notifications->markAllAsRead($user);
    }
}
