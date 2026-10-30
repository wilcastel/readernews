<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedSource extends Model
{
    protected $fillable = [
        'feed_id',
        'url',
    ];

    public function feed()
    {
        return $this->belongsTo(Feed::class);
    }
}
