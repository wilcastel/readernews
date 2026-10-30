<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feed extends Model
{
    protected $fillable = [
        'url',
        'selector',
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

    public function sources()
    {
        return $this->hasMany(FeedSource::class);
    }

    /**
     * Every URL to fetch for this feed: the primary URL first, then the extra sources.
     *
     * @return string[]
     */
    public function allUrls(): array
    {
        return collect([$this->url])
            ->merge($this->sources()->orderBy('id')->pluck('url'))
            ->unique()
            ->values()
            ->all();
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
