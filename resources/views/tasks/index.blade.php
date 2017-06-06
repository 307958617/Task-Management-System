@extends('layouts.app')
@section('content')
    <div class="container">
        <h1>所有任务</h1>
        <ul id="myTab" class="nav nav-tabs">
            <li class="active">
                <a href="#todo" data-toggle="tab">未完成任务</a>
            </li>
            <li>
                <a href="#done" data-toggle="tab">已完成任务</a>
            </li>
        </ul>
        <div id="myTabContent" class="tab-content">
            <div class="tab-pane fade in active" id="todo">
                <table class="table table-striped">
                    <tbody>
                    @foreach($todo as $task)
                        <tr>
                            <td class="title-cell">{{ $task->updated_at->diffForHumans() }}&nbsp;&nbsp;&nbsp;&nbsp;{{ $task->name }}</td>
                            <td class="title-cell">{{ $task->project->name }}</td>
                            <td class="icon-cell">@include('tasks._checkTaskForm')</td>
                            <td class="icon-cell">@include('tasks._editTaskModel')</td>
                            <td class="icon-cell">@include('tasks._deleteTaskForm')</td>
                        </tr>
                    @endforeach
                    {{ $todo->links() }}
                    </tbody>
                </table>
            </div>
            <div class="tab-pane fade" id="done">
                <table class="table table-striped">
                    <tbody>
                    @foreach($done as $task)
                        <tr>
                            <td class="title-cell">{{ $task->updated_at->diffForHumans() }}&nbsp;&nbsp;&nbsp;&nbsp;{{ $task->name }}</td>
                        </tr>
                    @endforeach
                    {{ $done->links() }}
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
