<?php

namespace App\Policies;

use App\Models\User;
use App\Models\WordOfTheDay;
use Spatie\Permission\Models\Permission;

class WordOfTheDayPolicy
{
    protected function allowManage(User $user): bool
    {
        if (hasRole('Admin') || hasRole('Super Admin')) {
            return true;
        }

        if (! \Illuminate\Support\Facades\Schema::hasTable('permissions')) {
            return false;
        }

        if (! Permission::query()->where('name', 'manage word of the day')->exists()) {
            return false;
        }

        return $user->can('manage word of the day');
    }

    public function viewAny(User $user): bool
    {
        return $this->allowManage($user);
    }

    public function view(User $user, WordOfTheDay $wordOfTheDay): bool
    {
        return $this->allowManage($user);
    }

    public function create(User $user): bool
    {
        return $this->allowManage($user);
    }

    public function update(User $user, WordOfTheDay $wordOfTheDay): bool
    {
        return $this->allowManage($user);
    }

    public function delete(User $user, WordOfTheDay $wordOfTheDay): bool
    {
        return $this->allowManage($user);
    }
}
