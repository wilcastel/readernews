<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feed extends Model
{
    protected $fillable = [
        'url',
        'name', 
        'website_url',
        'favicon',
        'folder_id',
        'is_rss',
        'last_scraped_at',
        'user_id'
    ];

    protected $casts = [
        'is_rss' => 'boolean',
        'last_scraped_at' => 'datetime',
    ];

    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    public function folder()
    {
        return $this->belongsTo(Folder::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
