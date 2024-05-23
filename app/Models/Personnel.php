<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Personnel extends Model
{
    protected $fillable = [
        'matricule_personnel', 'function_personnel', 'name_personnel', 'mail_presonnel', 'priority_personnel',
    ];

    protected $table = 'personnel';

    public function meetings()
    {
        return $this->hasMany(Meeting::class, 'animateur_id', 'matricule_personnel');
    }
}
