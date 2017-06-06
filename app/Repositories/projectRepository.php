<?php
namespace App\Repositories;

use App\Project;
use Illuminate\Support\Facades\Auth;
use Image;
class projectRepository
{
    public function projectsList()
    {
        return Auth::user()->projects()->get();
    }

    public function createProject($request)
    {
        return Auth::user()->projects()->create([
            'name' => $request->name,
            'thumbnail' => $this->thumbnail($request)
        ]);
    }

    public function updateProject($request,$id)
    {
        $project = Project::findOrFail($id);
        $project->name = $request->name;
        if($request->hasFile('thumbnail')){
            $project->thumbnail = $this->thumbnail($request);
        }
        $project->save();
    }

    public function destroyProject($id)
    {
        return Project::findOrFail($id)->delete();
    }

    public function thumbnail($request)
    {

        if($request->hasFile('thumbnail')){
            $file = $request->thumbnail;
            $name = str_random(10).'.jpg';
            $path = public_path('pictures/thumbnails/').$name;
            Image::make($file)->resize(300, 100)->save($path);
            return $name;
        }
        return $name='default.jpg';
    }
}