<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Models\Role;

class DefaultAccountManager
{
    public function ensureDefaultAccounts(): void
    {
        $this->ensureDefaultAdmin();
        $this->ensureDefaultCustomer();
    }

    public function ensureDefaultAdmin(): User
    {
        return $this->ensureDefaultUser(
            email: $this->defaultAdminEmail(),
            name: $this->defaultAdminName(),
            password: $this->defaultAdminPassword(),
            roleName: 'admin',
        );
    }

    public function ensureDefaultCustomer(): User
    {
        return $this->ensureDefaultUser(
            email: $this->defaultCustomerEmail(),
            name: $this->defaultCustomerName(),
            password: $this->defaultCustomerPassword(),
            roleName: 'customer',
        );
    }

    public function defaultAdminEmail(): string
    {
        return strtolower(trim((string) env('DEFAULT_ADMIN_EMAIL', 'admin@thriftshop.local')));
    }

    public function defaultAdminName(): string
    {
        return trim((string) env('DEFAULT_ADMIN_NAME', 'System Admin'));
    }

    public function defaultAdminPassword(): string
    {
        return (string) env('DEFAULT_ADMIN_PASSWORD', '123');
    }

    public function defaultCustomerEmail(): string
    {
        return strtolower(trim((string) env('DEFAULT_CUSTOMER_EMAIL', 'customer@thriftshop.local')));
    }

    public function defaultCustomerName(): string
    {
        return trim((string) env('DEFAULT_CUSTOMER_NAME', 'Default Customer'));
    }

    public function defaultCustomerPassword(): string
    {
        return (string) env('DEFAULT_CUSTOMER_PASSWORD', '123');
    }

    public function isDefaultAdmin(?User $user): bool
    {
        return $user instanceof User
            && strtolower(trim((string) $user->email)) === $this->defaultAdminEmail();
    }

    private function ensureDefaultUser(string $email, string $name, string $password, string $roleName): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );

        $role = Role::findOrCreate($roleName, 'web');

        if (! $user->hasRole($roleName)) {
            $user->assignRole($role);
        }

        $updates = [];

        if (! $user->is_active) {
            $updates['is_active'] = true;
            $updates['suspended_at'] = null;
        }

        if ($user->email_verified_at === null) {
            $updates['email_verified_at'] = now();
        }

        if ($updates !== []) {
            $user->forceFill($updates)->save();
        }

        return $user;
    }
}
