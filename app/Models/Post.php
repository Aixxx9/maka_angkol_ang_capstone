<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;


class NewsPost extends Model {
use SoftDeletes;
protected $fillable = ['author_id','title','slug','excerpt','body','cover_image_path'];
public function author(){ return $this->belongsTo(User::class, 'author_id'); }
}