<?php

namespace App\Models;

use App\Enums\PublicationStatus;
use Illuminate\Database\Eloquent\Model;

class WordPressPublication extends Model
{
    protected $table = 'wordpress_publications';

    protected $fillable = [
        'article_id',
        'wordpress_site_id',
        'status',
        'wp_post_id',
        'wp_post_url',
        'error_message',
        'rewritten_content',
        'published_at',
    ];

    protected $casts = [
        'status' => PublicationStatus::class,
        'published_at' => 'datetime',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class);
    }

    public function wordPressSite()
    {
        return $this->belongsTo(WordPressSite::class, 'wordpress_site_id');
    }
}
