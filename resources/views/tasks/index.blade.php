@extends('layouts.app')
@section('css')

@endsection
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
                            <td class="title-cell">{{ $task->updated_at->diffForHumans() }}&nbsp;&nbsp;&nbsp;&nbsp;<a href="{{ route('task.show',$task->id) }}">{{ $task->name }}</a></td>
                            <td class="title-cell">{{ $task->project->name }}</td>
                            <td class="icon-cell">@include('tasks._checkTaskForm')</td>
                            <td class="icon-cell">@include('tasks._editTaskModel')</td>
                            <td class="icon-cell">@include('tasks._deleteTaskForm')</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            {{ $todo->links() }} <!-- 分页导航 -->
            </div>
            <div class="tab-pane fade" id="done">
                <table class="table table-striped">
                    <tbody>
                    @foreach($done as $task)
                        <tr>
                            <td class="title-cell">{{ $task->updated_at->diffForHumans() }}&nbsp;&nbsp;&nbsp;&nbsp;{{ $task->name }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            {{ $done->links() }} <!-- 分页导航 -->
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script> <!-- 这是为了实现标签页刷新后任然留在当前选择的哪个不变的办法 -->
        $(document).ready(function () {
            if(window.localStorage.active == '已完成任务'){
                $('#myTab a[href="#done"]').tab('show');
            }
            $('a[data-toggle="tab"]').on('show.bs.tab', function (e) {
                var activeTab = $(e.target).text();
                window.localStorage.active = activeTab
            })
        });
    </script>
@endsection
