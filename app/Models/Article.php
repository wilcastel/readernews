<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'feed_id',
        'title',
        'url',
        'author',
        'image_url',
        'content',
        'summary',
        'published_at',
        'is_read',
        'is_saved',
        'is_favorite'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'is_read' => 'boolean',
        'is_saved' => 'boolean',
        'is_favorite' => 'boolean',
    ];

    public function feed()
    {
        return $this->belongsTo(Feed::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot(['is_read', 'is_saved', 'is_favorite'])
            ->withTimestamps();
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
}
