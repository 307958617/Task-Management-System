<?php

namespace App\Http\Controllers;

use App\Repositories\projectRepository;
use App\Repositories\taskRepository;
use Illuminate\Http\Request;

class ChartController extends Controller
{
    protected $task,$project;
    public function __construct(taskRepository $task,projectRepository $project)
    {
        $this->task = $task;
        $this->project = $project;
    }
    public function index()
    {
        $taskTotal = $this->task->total();
        $todoCount = $this->task->todoCount();
        $doneCount = $this->task->doneCount();
        $projectTotal = $this->project->total();
        $projectNameList = $this->project->projectNameList();
        $projects = $this->project->projects();
        return view('charts.index',compact('taskTotal','todoCount','doneCount','projectTotal','projectNameList','projects'));
    }
}
