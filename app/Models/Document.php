<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class Document extends Model {
protected $fillable = ['uploader_id','title','file_path'];
public function uploader(){ return $this->belongsTo(User::class,'uploader_id'); }
}