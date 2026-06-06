<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WordPressSite extends Model
{
    protected $table = 'wordpress_sites';

    protected $fillable = [
        'user_id',
        'name',
        'site_url',
        'username',
        'application_password',
        'is_active',
    ];

    protected $casts = [
        'application_password' => 'encrypted',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'application_password',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function publications()
    {
        return $this->hasMany(WordPressPublication::class);
    }
}
