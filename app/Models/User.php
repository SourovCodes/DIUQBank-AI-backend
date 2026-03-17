<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'email',
        'avatar',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function quickUploads(): HasMany
    {
        return $this->hasMany(QuickUpload::class);
    }

    public function reviewedQuickUploads(): HasMany
    {
        return $this->hasMany(QuickUpload::class, 'reviewer_id');
    }

    public function hasDeletionDependencies(): bool
    {
        return $this->submissions()->exists() || $this->quickUploads()->exists();
    }

    public function getDeletionDependencyMessage(): string
    {
        return 'Delete the user\'s submissions and quick uploads first.';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() !== 'admin') {
            return true;
        }

        if (app()->isLocal()) {
            return true;
        }

        if (blank($this->email_verified_at)) {
            return false;
        }

        $allowedEmails = $this->normalizedAdminAccessList(config('filament-admin.emails', []));
        $allowedUsernames = $this->normalizedAdminAccessList(config('filament-admin.usernames', []));

        if ($allowedEmails->isEmpty() && $allowedUsernames->isEmpty()) {
            return false;
        }

        return $allowedEmails->contains(Str::lower((string) $this->email))
            || $allowedUsernames->contains(Str::lower((string) $this->username));
    }

    public function scopeContributors(Builder $query): Builder
    {
        return $query->whereHas('submissions');
    }

    /**
     * @param  array<int, string>  $values
     * @return Collection<int, string>
     */
    protected function normalizedAdminAccessList(array $values): Collection
    {
        return collect($values)
            ->filter(static fn (mixed $value): bool => filled($value))
            ->map(static fn (mixed $value): string => Str::lower(trim((string) $value)))
            ->values();
    }
}
