<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    protected $fillable = ['name','project_id','description'];

    public function project()
    {
        return $this->belongsTo('App\Project');//任务是属于某个项目的
    }

    public function steps()
    {
        return $this->hasMany('App\Step');
    }
}
