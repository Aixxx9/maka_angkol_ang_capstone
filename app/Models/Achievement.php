<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class Achievement extends Model
{
    protected $fillable = ['school_id', 'year', 'title', 'description'];
    public function school() {
        return $this->belongsTo(School::class);
    }
}
