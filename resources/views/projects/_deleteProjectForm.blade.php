<form action="{{ route('project.destroy',$project->id) }}" method="post">
    {{method_field('DELETE')}}<!-- 这里需要注意，使用的是DELETE方法才能触发destory()方法，而不能直接用method='DELETE' -->
    {{csrf_field()}}
    <button>
        <i class="fa fa-close"></i>
    </button>
</form>