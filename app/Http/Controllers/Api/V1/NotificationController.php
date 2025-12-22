<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Notification;
use Illuminate\Http\Request;

class NotificationController extends ApiController
{
    public function index(Request $request)
    {
        $this->requirePermission('ViewAny:Notification');

        $user = $request->user();

        $query = Notification::query();

        $targetFilter = function ($builder) use ($user) {
            $builder->where('target_type', 'all')
                ->orWhere(function ($inner) use ($user) {
                    $inner->where('target_type', 'user')
                        ->where('target_id', $user->id);
                });

            if ($user->branch_id) {
                $builder->orWhere(function ($inner) use ($user) {
                    $inner->where('target_type', 'branch')
                        ->where('target_id', $user->branch_id);
                });
            }
        };

        $query->where($targetFilter);

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type'));
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        $paginator = $query->orderByDesc('created_at')->paginate($this->perPage());

        $items = $paginator->getCollection()->map(function (Notification $notification) {
            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'title' => $notification->title,
                'message' => $notification->message,
                'priority' => $notification->priority,
                'status' => $notification->status,
                'data' => $notification->data,
                'action_url' => $notification->action_url,
                'created_at' => $notification->created_at?->toIso8601String(),
            ];
        })->values()->all();

        $unreadCount = Notification::query()
            ->where($targetFilter)
            ->where('status', '!=', 'read')
            ->count();

        return $this->paginated($paginator, $items, [], [
            'unread_count' => $unreadCount,
        ]);
    }

    public function markRead(Notification $notification)
    {
        $this->requirePermission('Update:Notification');

        if (! $this->appliesToUser($notification, request()->user())) {
            return $this->error('RESOURCE_NOT_FOUND', 'المورد غير موجود', 404);
        }

        $notification->forceFill([
            'status' => 'read',
            'read_at' => now(),
        ])->save();

        return $this->success([
            'id' => $notification->id,
            'status' => $notification->status,
            'read_at' => $notification->read_at?->toIso8601String(),
        ], 'تم تعليم الإشعار كمقروء');
    }

    public function markAllRead(Request $request)
    {
        $this->requirePermission('Update:Notification');

        $user = $request->user();

        $query = Notification::query();
        $query->where(function ($builder) use ($user) {
            $builder->where('target_type', 'all')
                ->orWhere(function ($inner) use ($user) {
                    $inner->where('target_type', 'user')
                        ->where('target_id', $user->id);
                });

            if ($user->branch_id) {
                $builder->orWhere(function ($inner) use ($user) {
                    $inner->where('target_type', 'branch')
                        ->where('target_id', $user->branch_id);
                });
            }
        });

        $updated = $query->where('status', '!=', 'read')->update([
            'status' => 'read',
            'read_at' => now(),
        ]);

        return $this->success([
            'count' => $updated,
        ], 'تم تعليم جميع الإشعارات كمقروءة');
    }

    private function appliesToUser(Notification $notification, $user): bool
    {
        if ($notification->target_type === 'all') {
            return true;
        }

        if ($notification->target_type === 'user' && $notification->target_id === $user->id) {
            return true;
        }

        if ($notification->target_type === 'branch' && $user->branch_id) {
            return $notification->target_id === $user->branch_id;
        }

        return false;
    }
}
