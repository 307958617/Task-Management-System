<?php

namespace App\Http\Controllers;

use App\Http\Requests\createTaskRequest;
use App\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $projectList = Auth::user()->projects()->pluck('name','id');
        $todo = Auth::user()->tasks()->where('completed','F')->paginate(5);
        $done = Auth::user()->tasks()->where('completed','T')->paginate(5);
        return view('tasks.index',compact('todo','done','projectList'));
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
    public function store(createTaskRequest $request)
    {
        Task::create([
            'name'=> $request->name,
            'project_id' => $request->id
        ]);
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
        $task = Task::findOrFail($id);
        return view('tasks.show',compact('task'));
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
    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $task->update([
            'name'=>$request->name,
            'project_id'=>$request->projectList
        ]);
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
        Task::findOrFail($id)->delete();
        return back();
    }

    public function check($id)
    {
        $task = Task::findOrFail($id);
        $task->completed = 'T';
        $task->save();
        return back();
    }

    public function searchApi()
    {
        return Auth::user()->tasks;
    }
}
