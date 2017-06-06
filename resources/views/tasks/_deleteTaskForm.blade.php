{{--{!! Form::open(['route'=>['task.destroy',$task->id],'method'=>'DELETE']) !!}--}}
<form action="{{ route('task.destroy',$task->id) }}" method="post">
    {{ method_field('DELETE') }}
    {{ csrf_field() }}
    <button class="btn btn-xs btn-danger">
        <i class="fa fa-close"></i>
    </button>
</form>
{{--{!! Form::close() !!}--}}