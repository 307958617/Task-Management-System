<button class="btn btn-xs btn-primary" data-toggle="modal" data-target="#editModal-{{$task->id}}">
    <i class="fa fa-cog"></i>
</button>
<!-- 模态框（Modal） -->
<div class="modal fade" id="editModal-{{$task->id}}" tabindex="-1" role="dialog" aria-labelledby="editModal-{{$task->id}}-Label" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="myModalLabel">编辑任务</h4>
            </div>
            {{--{!! Form::model($task,['route'=>['task.update',$task->id],'files'=>true,'method'=>'PATCH']) !!}--}}
            <form action="{{ route('task.update',$task->id) }}" method="POST" enctype="multipart/form-data" >
                {{ method_field('PATCH') }}
                {{ csrf_field() }}
                <div class="modal-body">
                    <div class="form-group">
                        {{--{!! Form::label('title', '任务名称：', ['class' => 'control-label']) !!}--}}
                        {{--{!! Form::text('title', null, ['class' => 'form-control']) !!}--}}
                        <label for="name" class="control-label">任务名称：</label>
                        <input type="text" id="name" name="name" class="form-control" value="{{ $task->name }}">
                    </div>
                    <div class="form-group">
                        {{--{!! Form::label('projectList', '所属项目：', ['class' => 'control-label']) !!}--}}
                        {{--{!! Form::select('projectList',$projectList,null,['class' => 'form-control']) !!}--}}
                        <label for="projectList" class="control-label">所属项目：</label>
                        <select name="projectList" id="projectList" class="form-control">
                            @foreach($projectList as $key=>$pro)
                                <option {{ $task->project->id==$key?"selected":"" }} value="{{ $key }}">{{ $pro }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    {{--{!! Form::submit('提交更改',['class'=>'btn btn-primary']) !!}--}}
                    <button type="submit" class="btn btn-primary">提交更改</button>
                </div>
            </form>
            {{--{!! Form::close() !!}--}}
        </div><!-- /.modal-content -->
    </div><!-- /.modal -->
</div>