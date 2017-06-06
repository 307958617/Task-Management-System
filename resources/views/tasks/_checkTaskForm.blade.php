{{--{!! Form::open(['route' => ['task.check',$task->id],'method'=>'PATCH']) !!}--}}
<form action="{{ route('task.check',$task->id) }}" method="post">
    {{ method_field('PATCH') }}
    {{ csrf_field() }}
    <button type="submit" class="btn btn-success btn-xs">
        <i class="fa fa-check-square-o"></i>
    </button>
</form>
{{--{!! Form::close() !!}--}}