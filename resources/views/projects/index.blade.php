@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <div class="container">
        <div class="row">
            {{--显示该用户所有项目--}}
            @foreach($projects as $project)
                <div class="col-sm-6 col-md-3">
                    <div class="thumbnail">
                        <!-- 控制条用来显示修改，删除项目的按钮 -->
                        <div class="control-bar text-right">
                            @include('projects._deleteProjectForm')
                            <button data-toggle="modal" data-target="#editProjectModal-{{ $project->id }}">
                                <i class="fa fa-cog"></i>
                            </button>
                        </div>
                        <a href="{{route('project.show',$project->id)}}">
                            <img src="{{ asset('pictures/thumbnails/'.$project->thumbnail) }}" alt="{{ $project->name }}">
                            <div class="caption">
                                <h3 class="text-center">{{ $project->name }}</h3>
                            </div>
                        </a>
                    </div>
                </div>
                @include('projects._editProjectModel') <!-- 这个是#editModel的部分代码，不能放到按钮下面，只能放到这里不然会产生只有鼠标放到上面才会显示，不然就显示不出来的问题 -->
            @endforeach
            {{--创建项目--}}
            @include('projects._createProjectModel')
        </div>
    </div>
@endsection