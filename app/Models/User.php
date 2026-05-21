<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Panel;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar, HasMedia
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable, SoftDeletes;

    use HasRoles;
    use InteractsWithMedia;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
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

    protected $appends = [
        'roles_display',
        'joined_at_display',
    ];

    public function getRolesDisplayAttribute(): string
    {
        return $this->getRoleNames()->implode(', ') ?: '—';
    }

    public function getJoinedAtDisplayAttribute(): string
    {
        return $this->created_at?->translatedFormat('d F Y') ?: '—';
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasRole('admin') || $this->hasRole('super-admin');

        // atau gunakan permission:
        // return $this->can('access panel');
    }

    public function getFilamentAvatarUrl(): ?string
    {
        $url = $this->getFirstMediaUrl('avatars', 'preview');

        return $url ?: $this->defaultAvatarUrl();
    }

    protected function defaultAvatarUrl(): string
    {
        // Generate default avatar dari nama user
        return 'https://ui-avatars.com/api/?name='.urlencode($this->name).'&color=7F9CF5&background=EBF4FF';
    }

    /**
     * Register media collections for the model.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatars')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
            ->registerMediaConversions(function (Media $media) {
                $this->addMediaConversion('thumb')
                    ->width(100)
                    ->height(100)
                    ->sharpen(10)
                    ->optimize();

                $this->addMediaConversion('preview')
                    ->width(300)
                    ->height(300)
                    ->optimize();
            });
    }
}
