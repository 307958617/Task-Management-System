{{--{!! Form::open(['route' => ['task.store','id'=> $project->id ],'class'=>'form-horizontal']) !!}--}}
<form action="{{ route('task.store',['id'=> $project->id]) }}" method="post" class="form-horizontal">
    {{ csrf_field() }}
    <div class="form-group">
        <label for="createTask" class="col-md-3 control-label">{{ $project->name }}</label>
        <div class="col-md-7">
            <input type="text" class="form-control" name="name" placeholder="你需要添加任务吗？">
            @if ($errors->has('name'))
                <span class="help-block alert-danger">
                    <strong>{{ $errors->first('name') }}</strong>
                </span>
            @endif
        </div>
        <div class="col-sm-1">
            <button type="submit" class="btn btn-primary">
                <i class="fa fa-plus"></i>
            </button>
        </div>
    </div>
</form>
{{--{!! Form::close() !!}--}}