<?php
namespace App\Http\ViewComposers;

use App\Repositories\taskRepository;
use Illuminate\View\View;

class TaskCountComposer
{
    public function __construct(taskRepository $task)
    {
        $this->task = $task;
    }

    public function compose(View $view)
    {
        $view->with([
            'total' => $this->task->total(),
            'doneCount' => $this->task->doneCount(),
            'todoCount' => $this->task->todoCount(),
        ]);
    }
}