<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

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
    public function feeds()
    {
        return $this->hasMany(Feed::class);
    }

    public function folders()
    {
        return $this->hasMany(Folder::class);
    }

    public function tags()
    {
        return $this->hasMany(Tag::class);
    }

    public function articles()
    {
        return $this->belongsToMany(Article::class)
            ->withPivot(['is_read', 'is_saved', 'is_favorite'])
            ->withTimestamps();
    }

    public function unreadArticlesCount()
    {
        // Articles in user's feeds that are NOT marked as read
        return Article::whereHas('feed', function($q) {
            $q->where('user_id', $this->id);
        })->whereDoesntHave('users', function($q) {
            $q->where('user_id', $this->id)->where('is_read', true);
        })->count();
    }

    public function savedArticlesCount()
    {
        return $this->articles()->wherePivot('is_saved', true)->count();
    }

    public function favoriteArticlesCount()
    {
        return $this->articles()->wherePivot('is_favorite', true)->count();
    }
}
