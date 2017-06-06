<!-- 按钮触发模态框 -->
<button class="btn btn-primary btn-lg" data-toggle="modal" data-target="#createProjectModal">
    <i class="fa fa-plus"></i>
</button>
<!-- 模态框（Modal） -->
<div class="modal fade" id="createProjectModal" tabindex="-1" role="dialog" aria-labelledby="createProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h4 class="modal-title" id="createProjectModalLabel">创建项目</h4>
            </div>
            <div class="modal-body">
                <form action="{{ route('project.store') }}" method="POST" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <label for="name">项目名称</label>
                        <input type="text" class="form-control" id="name" name="name" placeholder="请输入项目名称">
                    </div>
                    <div class="form-group">
                        <label for="thumbnail">项目缩略图</label>
                        <input type="file" id="thumbnail" name="thumbnail">
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">创建项目</button>
                    </div>
                </form>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal -->
</div>