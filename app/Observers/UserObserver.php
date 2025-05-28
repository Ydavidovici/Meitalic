<?php

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

class UserObserver
{
    public function created(User $user)
    {
        Log::channel('audit')->info('User created', [
            'model'        => 'User',
            'action'       => 'created',
            'id'           => $user->id,
            'email'        => $user->email,
            'is_admin'     => $user->is_admin,
            'performed_by' => auth()->id() ?: 'system',
            'timestamp'    => now()->toIso8601String(),
        ]);
    }

    public function updated(User $user)
    {
        $changes = $user->getChanges();
        Log::channel('audit')->info('User updated', [
            'model'        => 'User',
            'action'       => 'updated',
            'id'           => $user->id,
            'changes'      => $changes,
            'performed_by' => auth()->id(),
            'timestamp'    => now()->toIso8601String(),
        ]);
    }

    public function deleted(User $user)
    {
        Log::channel('audit')->info('User deleted', [
            'model'        => 'User',
            'action'       => 'deleted',
            'id'           => $user->id,
            'email'        => $user->email,
            'performed_by' => auth()->id(),
            'timestamp'    => now()->toIso8601String(),
        ]);
    }
}
