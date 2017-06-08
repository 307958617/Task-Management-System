<?php
namespace App\Repositories;

use App\Task;

class taskRepository
{
    public function total()
    {
        $total = Task::all()->count();
        return $total;
    }

    public function todoCount()
    {
        $todoCount = Task::where('completed','F')->count();
        return $todoCount;
    }

    public function doneCount()
    {
        $doneCount = Task::where('completed','T')->count();
        return $doneCount;
    }
}