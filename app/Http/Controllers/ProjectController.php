<?php

namespace App\Http\Controllers;

use App\Http\Requests\createProjectRequest;
use App\Repositories\projectRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    protected $repo;

    public function __construct(projectRepository $repo)
    {
        $this->middleware('auth');
        $this->repo = $repo;
    }

    public function index()
    {
        $projects = $this->repo->projectsList();
        return view('projects.index',compact('projects'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(createProjectRequest $request)
    {
        $this->repo->createProject($request);
        return back();
    }



    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $project = Auth::user()->projects()->where('id',$id)->first();//获取当前用户当前项目
        $projectList = Auth::user()->projects()->pluck('name','id');//获取当前用户任务的键值对数组
        $todo = $project->tasks()->where('completed','F')->get();//获取未完成任务
        $done = $project->tasks()->where('completed','T')->get();//获取已完成任务
        return view('projects.show',compact('project','projectList','todo','done'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(createProjectRequest $request, $id)
    {
        $this->repo->updateProject($request,$id);
        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->repo->destroyProject($id);
        return back();
    }
}
