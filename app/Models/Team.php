<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class Team extends Model {
protected $fillable = ['school_id','sport_id','name','season'];
public function school(){ return $this->belongsTo(School::class); }
public function sport(){ return $this->belongsTo(Sport::class); }
public function players(){ return $this->hasMany(Player::class); }
}