<?php
namespace App\Http\Controllers;
use App\Models\Document;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;


class DocumentController extends Controller
{
public function index(){
return Inertia::render('Documents/Index', [
'docs'=> Document::latest()->get(['id','title','file_path','created_at'])
]);
}
public function store(Request $req){
$data=$req->validate(['title'=>'required','file'=>'required|file']);
$path=$req->file('file')->store('docs','public');
Document::create(['title'=>$data['title'],'file_path'=>$path,'uploader_id'=>auth()->id()]);
return back();
}
public function download(Document $document){
return Storage::disk('public')->download($document->file_path, basename($document->file_path));
}
}