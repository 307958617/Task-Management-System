<?php

namespace App\Http\Controllers;

use App\Step;
use App\Task;
use Illuminate\Http\Request;

class StepController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($id)
    {
        $steps = Task::findOrFail($id)->steps;
        return $steps;
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
    public function store($id,Request $request)
    {
        Task::findOrFail($id)->steps()->create([
            'name'=>$request->name
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
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
    public function update($taskID,Request $request, $id)
    {
        $step = Step::findOrFail($id);
        $step->update([
            'name' => $request->name
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($taskID,$id)
    {
        Step::findOrFail($id)->delete();
    }

    public function toggleComplete($taskID,$id)
    {
        $step = Step::findOrFail($id);
        $step->update([
            'completed' => !$step->completed
        ]);
    }

    public function completeAll($taskID)
    {
        Task::findOrFail($taskID)->steps()->update([
            'completed' => 1
        ]);
    }

    public function clearCompleted($taskID)
    {
        Task::findOrFail($taskID)->steps()->where('completed',1)->delete();
    }
}
