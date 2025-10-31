<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class School extends Model {
use HasFactory;
protected $fillable = ['name','slug','logo_path','summary','location'];
public function teams(){ return $this->hasMany(Team::class); }
public function achievements(){ return $this->hasMany(Achievement::class); }

}