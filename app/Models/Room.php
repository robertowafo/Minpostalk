<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = [
        'room_id', 'room_name', 'room_capacity', 'room_availability',
    ];

    protected $table = 'room';

    
    public function room()
    {
        return $this->hasMany(Room::class, 'room_id', 'room_id');
    }
}
