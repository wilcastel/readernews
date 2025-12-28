<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Folder extends Model
{
    protected $fillable = ['name'];

    public function feeds()
    {
        return $this->hasMany(Feed::class);
    }
}
