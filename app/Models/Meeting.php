<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Meeting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'matricule', 'intitule_reunion', 'animateur', 'date_reunion', 'heure_debut', 'number_participant', 'meeting_room',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'meeting';

    
}
