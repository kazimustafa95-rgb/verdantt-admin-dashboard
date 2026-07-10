<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Contracts\Auth\Authenticatable;

class AdminUser implements Authenticatable, FilamentUser, HasAvatar, HasName
{
    public function __construct(
        public int $id,
        public string $name,
        public string $email,
        public string $role,
        public string $apiToken,
        public ?string $profileImage = null,
    ) {
    }

    public static function fromApiPayload(array $user, string $token): self
    {
        return new self(
            id: (int) $user['id'],
            name: trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')) ?: $user['email'],
            email: $user['email'],
            role: $user['role'] ?? 'admin',
            apiToken: $token,
            profileImage: $user['profile_image'] ?? null,
        );
    }

    public function toCacheArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'apiToken' => $this->apiToken,
            'profileImage' => $this->profileImage,
        ];
    }

    public static function fromCacheArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            email: $data['email'],
            role: $data['role'],
            apiToken: $data['apiToken'],
            profileImage: $data['profileImage'] ?? null,
        );
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return in_array($this->role, ['admin', 'super_admin'], true);
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->profileImage ?: null;
    }

    public function getKey(): int
    {
        return $this->id;
    }

    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    public function getEmailForPasswordReset(): string
    {
        return $this->email;
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): int
    {
        return $this->id;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void
    {
        //
    }

    public function getRememberTokenName(): string
    {
        return '';
    }
}
