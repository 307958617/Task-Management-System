<p align="center"><img src="https://laravel.com/assets/img/components/logo-laravel.svg"></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/d/total.svg" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/v/stable.svg" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://poser.pugx.org/laravel/framework/license.svg" alt="License"></a>
</p>

# 步骤一：配置数据库环境：
## 1、env改为：
    DB_DATABASE=task  //数据库名称
    DB_USERNAME=homestead   //数据库用户名
    DB_PASSWORD=secret  //数据库密码
## 2、在数据库里面创建一个名为task的数据库。
# 步骤二：生成用户登陆验证：
## 1、执行laravel自带命令生成登陆验证相关文件：
    php artisan make:auth
## 2、重新设计用户表即修改database\migrations\create_users_table数据库迁移文件内容如下:
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->string('avatar')->default('default.jpg');//增加用户头像，默认头像为default.jpg
            $table->string('confirmation_token');//**验证邮箱的token,默认每个注册的用户都有会产生一个,！！这里需要注意！！，不要忘了到User model里面的fillable属性添加该字段，不然没有默认值是不能使用create()方法来保存数据的。
            $table->string('is_active')->default('F');//增加注册后判断是否通过邮箱验证，默认为没有通过
            $table->timestamps();
        });
    }
## 3、将迁移文件的表写到数据库中，执行如下命令：
    php artisan migrate
## 4、要实现必须邮箱验证后才能进入系统，而不是系统自带的已注册就可以直接进入，需要做到如下几步：
### ①、生成带有相应 Markdown 模板的可邮寄类Registered.php用来发送注册时的验证邮件,使用如下命令：
    php artisan make:mail Registered --markdown=emails.registered //注意，执行这条命令后就会产生App\Mail\Registered.php这个可发送邮件类，和views\emails\registered.blade.php这个具体邮件内容文件。
### ②、修改刚刚生成的可邮寄类App\Mail\Registered.php：
    <?php
    
    namespace App\Mail;
    
    use App\User;
    use Illuminate\Bus\Queueable;
    use Illuminate\Mail\Mailable;
    use Illuminate\Queue\SerializesModels;
    use Illuminate\Contracts\Queue\ShouldQueue;
    
    class Registered extends Mailable implements ShouldQueue //这里添加implements ShouldQueue，可以在发送邮件时自动推送到队列
    {
        use Queueable, SerializesModels;

        public $user; //这里用公共（public）属性在视图中自动生效
    
        public function __construct(User $user) //依赖注入User Model，为什么用到User，这是因为需要在视图里面调用User的confirmation_token等字段数据
        {
            $this->user = $user;
        }
         
        public function build()
        {
            return $this->markdown('emails.registered');
        }
    }
### ③、修改刚刚生成的可邮寄类视图views\emails\registered.blade.php:
    @component('mail::message')
    # 尊敬的用户：{{ $user->name }}
    
    请点击下面的按钮，激活邮箱，并登录.
    
    @component('mail::button', ['url' => route('verify.register',['token'=>$user->confirmation_token])])
    激活邮箱
    @endcomponent
    
    
    如果按钮不能生效，请直接复制下面连接到浏览器地址栏进行激活。<br>
    {{ route('verify.register',['token'=>$user->confirmation_token]) }}
    # 谢谢！<br>
    @endcomponent
### ④、到web.php路由文件创建一个名为：verify.register的路由：
    Route::get('/verify/{token}','Auth\RegisterController@verify')->name('verify.register');
### ⑤、修改Controllers\Auth\RegisterController.php里面的create()方法：
    protected function create(array $data)
    {
        return User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'confirmation_token' => str_random('40'),//随机生成登陆邮箱验证token,！！注意！！这个值不能在设计数据迁移文件时设置默认值，因为如果那样的话，这个token全部都是一样的不会变了。
            'password' => bcrypt($data['password']),
        ]);
    }
### ⑥、直接修改Controllers\Auth\RegisterController.php里面RegistersUsers里面的register()方法：
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        event(new Registered($user = $this->create($request->all())));
        //发送邮件给注册的邮箱
        $email = $user->email;
        Mail::to($email)->send(new \App\Mail\Registered($user));

        //$this->guard()->login($user);//因为要发送邮件并且验证后才能登陆，所以这里就不能立刻登陆了
        //注册成功后提示需要验证邮箱才能登陆
        flash('你已经注册成功，请登陆你的邮箱：'.$email.'进行验证后才可登陆本网站！')->success()->important();
        //return $this->registered($request, $user)
        //                ?: redirect($this->redirectPath());
        return redirect('/login');
    }
### 实现第⑥步注册之后提示到哪个邮箱进行验证登陆，使用laracasts/flash：
#### step1:在GitHub上搜索laracasts/flash，按部就班的安装使用即可。然后想在什么地方用就放到什么地方
#### step2:需要注意：需要将下面的代码放到layouts里面的app.blade.php里面以显示
         <div class="container">
             @include('flash::message')
         </div>
#### step3：同时在<script src="{{ asset('js/app.js') }}"></script>下面添加如下代码因为是基于jquery的：
           <script>
               $('#flash-overlay-modal').modal();//用于显示模板
               $('div.alert').not('.alert-important').delay(3000).fadeOut(350);//用于控制显示时间
           </script>
#### step4:在Controllers\Auth\RegisterController.php里面添加如下代码：
    //注册成功后提示需要验证邮箱才能登陆
    flash('你已经注册成功，请登陆你的邮箱：'.$email.'进行验证后才可登陆本网站！')->success()->important();
### ⑦、在Controllers\Auth\RegisterController.php里面添加verify()方法：
    public function verify($token)
    {
        $user = User::where('confirmation_token',$token)->first();
        if (is_null($user)){
            //如果该用户不存在怎么样...
            return redirect('/register');
        }
        $user->is_active = 'T';  //激活用户
        $user->confirmation_token = str_random(40); //重置token
        $user->save();
        Auth::login($user);//登录
        return redirect('/home');//跳转
    }
## 5、要对登录时进行限制，没有激活邮箱的是不能登录的，即如果is_active = F,是不能登陆的：
### 在Controllers\Auth\LoginController.php里面重构login()方法和attemptLogin()方法：
    public function login(Request $request)
    {
        $this->validateLogin($request);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            return $this->sendLockoutResponse($request);
        }

        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        $this->incrementLoginAttempts($request);

        return $this->sendFailedLoginResponse($request);
    }

    protected function attemptLogin(Request $request)
    {
        $credentials = array_merge($this->credentials($request),['is_active' => 'T']);//添加is_active字段进去判断是否用邮箱激活
        return $this->guard()->attempt(
            $credentials, $request->has('remember')
        );
    }
# 步骤三、实现添加项目、删除项目、修改项目功能：
## 1、设计项目表projects也叫数据库迁移文件，以及定义该表与users表之间的关系：
### ①、执行如下命令生成Project Model的同时生成相应的数据库迁移文件：
    php artisan make:model Project -m
### ②、修改刚刚生成的数据库迁移文件create_projects_table.php内容如下：
    public function up()
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('user_id');//创建该项目的用户id
            $table->string('name')->unique();//项目名称，唯一不能重复
            $table->string('thumbnail')->nullable();//项目缩略图
            $table->timestamps();
        });
    }
### ③、在刚刚生成的Project Model里面定义projects表与users表之间的关联关系：
    public function user()
    {
        return $this->belongsTo('App\User');
    }
### ④、在User Model里面定义users表与projects表之间的关联关系：
    public function projects()
    {
        return $this->hasMany('App\Project');
    }
### ⑤、执行如下命令，将projects表写入数据库：
    php artisan migrate
## 2、创建views\projects\index.blade.php为显示所有项目,以及创建，编辑，修改，删除项目的页面，内容为：
### ①、在此之前先要做好准备工作，为views\layouts\app.blade.php增加css和js模块，代码如下：
    <!-- Styles -->
        <link href="{{ asset('css/app.css') }}" rel="stylesheet">
        @yield('css') <!-- 在这里为css引入做准备 -->
    <!-- Scripts -->
        <script src="{{ asset('js/app.js') }}"></script>
        @yield('js') <!-- 在这里为js引入做准备 -->
### ②、views\projects\index.blade.php的内容为：
    @extends('layouts.app')
    @section('css')
        <!-- 引入font-awesome -->
        <link href="https://cdn.bootcss.com/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet">
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
                                <button data-toggle="model" data-target="#editModel-{{$project->id}}">
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
### ③、views\projects\_createProjectModel.blade.php的内容为：
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
### ④、views\projects\_deleteProjectForm.blade.php的内容为：
    <form action="{{ route('project.destroy',$project->id) }}" method="post">
        {{method_field('DELETE')}}<!-- 这里需要注意，使用的是DELETE方法才能触发destory()方法，而不能直接用method='DELETE' -->
        {{csrf_field()}}<!-- 这里也是每个form必不可少的 -->
        <button>
            <i class="fa fa-close"></i>
        </button>
    </form>
### ⑤、views\projects\_editProjectModel.blade.php的内容为：
    <!-- 模态框（Modal） -->
    <div class="modal fade" id="editProjectModal-{{ $project->id }}" tabindex="-1" role="dialog" aria-labelledby="editProjectModal-{{ $project->id }}-Label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                    <h4 class="modal-title" id="myModalLabel">编辑项目</h4>
                </div>
                <div class="modal-body">
                    <form action="{{ route('project.update',$project->id) }}" method="POST" enctype="multipart/form-data">
                        {{ method_field('PATCH') }}<!-- 这里需要注意，使用的是PATCH方法才能触发update()方法，而不能直接用method='PATCH' -->
                        {{ csrf_field() }}
                        <div class="form-group">
                            <label for="name">项目名称</label>
                            <input type="text" class="form-control" id="name" name="name" placeholder="请输入项目名称" value="{{ $project->name }}">
                        </div>
                        <div class="form-group">
                            <label for="thumbnail">项目缩略图</label>
                            <input type="file" id="thumbnail" name="thumbnail">
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">编辑项目</button>
                        </div>
                    </form>
                </div>
            </div><!-- /.modal-content -->
        </div><!-- /.modal -->
    </div>
### ⑥、为2里面的视图添加css样式，在resources\assets\sass\目录下，创建一个styles.scss文件，用来写自定义的样式，内容如下：
    .thumbnail{
        padding: 0;
        position: relative;
        .control-bar {
          background-color: rgba(66, 217, 49, 0.5);
          position: absolute;
          width: 100%;
          color: #d94316;
          height: 30px;
          line-height: 30px;
          border:0;
          padding-right: 5px;
          display: none;
          button {
            border:none;
            background-color: transparent;
            float: right;
          }
        }
        .caption {
          padding: 0;
        }
    }
    .thumbnail:hover {
      .control-bar {
        display: block;
      }
    }
    
    label[for="createTask"].control-label {
      font-size: 35px;
      margin-top: -17px;
      text-align: left;
    }
    
    .icon-cell {
      width: 22px;
    }
### ⑦、将第⑥创建的styles.scss文件引入进sass\app.scss里面，在里面添加代码：
    @import "styles";
### ⑧、对新加的css样式进行编译，才能生效，运行如下代码：
    npm install cnpm -g --registry=https://registry.npm.taobao.org  //安装cnpm
    cnpm install //安装npm依赖
    npm run dev //进行编译，编译的配置文件为：webpack.mix.js
## 3、创建public\pictures\thumbnails目录用来存放项目缩略图
## 4、为Project Model创建名为：ProjectController控制器并创建资源路由，执行如下命令：
    php artisan make:controller ProjectController --resource
## 5、在web.php路由文件里面添加资源路由：
    Route::resource('project','ProjectController');
## 6、修改ProjectController里面的index()方法用来显示该用户所有项目：
    public function index()//显示该用户所有项目
    {
        $projects = Auth::user()->projects()->get();//该登录用户的项目列表
        return view('projects.index',compact('projects'));
    }
## 7、修改ProjectController里面的store()方法用来创建新的项目：
### ①、因为需要上传项目缩略图，所以先要引入图片上传工具intervention/image：
    具体参考相关文档：http://image.intervention.io/getting_started/installation#laravel
### ②、修改store()方法为：
    public function store(Request $request)
    {
       Auth::user()->projects()->create([ //这里需要注意，因为要关联是谁创建的，所以需要用到Auth::user()
           'name' => $request->name,
           'thumbnail' => $this->thumbnail($request)
       ]);
       return back();
    }
    
    public function thumbnail($request)
    {
       if($request->hasFile('thumbnail')){
           $file = $request->thumbnail;
           $name = str_random(10).'.jpg';
           $path = public_path('pictures/thumbnails/').$name;
           Image::make($file)->resize(300, 100)->save($path);
           return $name;
       }
       return $name='default.jpg';  //如果没有图片，那么就用默认的图片
    }
## 8、修改ProjectController里面的destroy()方法用来删除的项目：
    public function destroy($id)
    {
        Project::findOrFail($id)->delete();
        return back();
    }
## 9、修改ProjectController里面的update()方法用来编辑更新项目：
    public function update(Request $request, $id)
    {
        $project = Project::findOrFail($id);
        $project->name = $request->name;
        if($request->hasFile('thumbnail')){
            $project->thumbnail = $this->thumbnail($request);
        }
        $project->save();
        return back();
    }
## 10、如果要实现必须登录才能看见，需要在ProjectController里面添加__construct()方法：
    public function __construct()
    {
        $this->middleware('auth');//只有登录才能看见
    }
## 11、对ProjectController代码进行重构，使用repository来实现：
### ①、因为laravel没有在底层封装repository命令，所以我们自己创建，App\Repositories文件夹用来放置所有的repository:
### ②、在App\Repositories里面创建一个projectRepository.php,内容为：
    <?php
    namespace App\Repositories;//注意这里命名空间一定要对
    
    use App\Project;
    use Illuminate\Support\Facades\Auth;
    use Image;
    class projectRepository
    {
        public function projectsList()
        {
            return Auth::user()->projects()->get();
        }
    
        public function createProject($request)
        {
            return Auth::user()->projects()->create([
                'name' => $request->name,
                'thumbnail' => $this->thumbnail($request)
            ]);
        }
    
        public function updateProject($request,$id)
        {
            $project = Project::findOrFail($id);
            $project->name = $request->name;
            if($request->hasFile('thumbnail')){
                $project->thumbnail = $this->thumbnail($request);
            }
            $project->save();
        }
    
        public function destroyProject($id)
        {
            return Project::findOrFail($id)->delete();
        }
    
        public function thumbnail($request)
        {
    
            if($request->hasFile('thumbnail')){
                $file = $request->thumbnail;
                $name = str_random(10).'.jpg';
                $path = public_path('pictures/thumbnails/').$name;
                Image::make($file)->resize(300, 100)->save($path);
                return $name;
            }
            return $name='default.jpg';
        }
    }
### ③、在ProjectController里面引入刚刚创建的projectRepository:
    
    use App\Repositories\projectRepository;//这里一定不能少
    
    protected $repo;
    
    public function __construct(projectRepository $repo)
    {
        $this->middleware('auth');
        $this->repo = $repo;
    }
### ④、重构后的ProjectController代码如下（十分之简洁）：
    <?php
    
    namespace App\Http\Controllers;
   
    use App\Repositories\projectRepository;
    use Illuminate\Http\Request;
    
    class ProjectController extends Controller
    {
        
        protected $repo;
    
        public function __construct(projectRepository $repo)
        {
            $this->middleware('auth');
            $this->repo = $repo;
        }
    
        public function index()
        {
            $projects = $this->repo->projectsList();
            return view('projects.index',compact('projects'));
        }     
        
        public function store(Request $request)
        {
            $this->repo->createProject($request);
            return back();
        }
    
        public function update(Request $request, $id)
        {
            $this->repo->updateProject($request,$id);
            return back();
        }
    
        public function destroy($id)
        {
            $this->repo->destroyProject($id);
            return back();
        }
    }
# 步骤四、实现项目所属任务的增加、删除、修改、显示功能：
## 1、设计任务表tasks也叫数据库迁移文件，以及定义该表与projects表之间的关系：
### ①、执行如下命令生成Task Model的同时生成相应的数据库迁移文件：
    php artisan make:model Task -m
### ②、修改刚刚生成的数据库迁移文件create_tasks_table.php内容如下：
    public function up()
    {
        Schema::create('tasks', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('project_id');//任务隶属哪个项目的id
            $table->string('name');//任务名称
            $table->string('description');//任务描述内容
            $table->string('completed')->default('F');//任务完成情况，默认未完成
            $table->timestamps();
        });
    }
### ③、在刚刚生成的Task Model里面定义tasks表与projects表之间的关联关系：
    public function project()
    {
        return $this->belongsTo('App\Project');//任务是属于某个项目的
    }
### ④、在Project Model里面定义projects表与tasks表之间的关联关系：
    public function tasks()
    {
        return $this->hasMany('App\Task');
    }
### ⑤、！！还需要在User Model里面定义users与tasks表之间的关联关系：
    public function tasks()
    {
        return $this->hasManyThrough('App\Task','App\Project');//表示User通过Project间接拥有任务Task
    }
## 2、创建views\projects\show.blade.php为显示当前项目的所有任务,以及创建，编辑，修改，删除任务的页面，内容为：
### ①、views\projects\show.blade.php用来显示当前项目的任务，并在里面添任务及操作任务,内容为：
     @extends('layouts.app')
     @section('css')
         <link href="https://cdn.bootcss.com/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet">
     @endsection
     @section('content')
         <div class="container">
             @include('tasks._createTaskForm')
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
                                 <td class="icon-cell">@include('tasks._checkTaskForm')</td>
                                 <td class="icon-cell">@include('tasks._editTaskModel')</td>
                                 <td class="icon-cell">@include('tasks._deleteTaskForm')</td>
                             </tr>
                         @endforeach
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
                         </tbody>
                     </table>
                 </div>
             </div>
         </div>
     @endsection
     
     @section('js')
         <script>
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
### ②、创建views\tasks\index.blade.php用来显示所有任务，内容为：
    @extends('layouts.app')
    @section('css')
        <link href="https://cdn.bootcss.com/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet">
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
                                <td class="title-cell">{{ $task->updated_at->diffForHumans() }}&nbsp;&nbsp;&nbsp;&nbsp;{{ $task->name }}</td>
                                <td class="title-cell">{{ $task->project->name }}</td>
                                <td class="icon-cell">@include('tasks._checkTaskForm')</td>
                                <td class="icon-cell">@include('tasks._editTaskModel')</td>
                                <td class="icon-cell">@include('tasks._deleteTaskForm')</td>
                            </tr>
                        @endforeach
                        {{ $todo->links() }} <!-- 分页导航 -->
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
                        {{ $done->links() }} <!-- 分页导航 -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endsection
### ③、views\tasks\_createTaskForm.blade.php，即创建任务功能的表单内容为：
    {{--{!! Form::open(['route' => ['task.store','id'=> $project->id ],'class'=>'form-horizontal']) !!}--}}
    <form action="{{ route('task.store',['id'=> $project->id]) }}" method="post" class="form-horizontal">
        {{ csrf_field() }}
        <div class="form-group">
            <label for="createTask" class="col-md-3 control-label">{{ $project->name }}</label>
            <div class="col-md-7">
                <input type="text" class="form-control" name="name" placeholder="你需要添加任务吗？">
            </div>
            <div class="col-sm-1">
                <button type="submit" class="btn btn-primary">
                    <i class="fa fa-plus"></i>
                </button>
            </div>
        </div>
    </form>
    {{--{!! Form::close() !!}--}}
### ④、views\tasks\_checkTaskForm.blade.php，即将任务标记完成的功能表单,内容为：
    {{--{!! Form::open(['route' => ['task.check',$task->id],'method'=>'PATCH']) !!}--}}
    <form action="{{ route('task.check',$task->id) }}" method="post">//这里的method="post"仍然不能少啊
        {{ method_field('PATCH') }}
        {{ csrf_field() }}
        <button type="submit" class="btn btn-success btn-xs">
            <i class="fa fa-check-square-o"></i>
        </button>
    </form>
    {{--{!! Form::close() !!}--}}
### ⑤、views\tasks\_editTaskModel.blade.php,编辑任务，内容为：
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
### ⑥、views\tasks\_deleteTaskForm.blade.php,删除任务，内容为：
    {{--{!! Form::open(['route'=>['tasks.destroy',$task->id],'method'=>'DELETE']) !!}--}}
    <form action="{{ route('task.destory',$task->id) }}" method="post">
        {{ method_field('DELETE') }}
        {{ csrf_field() }}
        <button class="btn btn-xs btn-danger">
            <i class="fa fa-close"></i>
        </button>
    </form>
    {{--{!! Form::close() !!}--}}
## 3、为Task Model创建名为：TaskController控制器并创建资源路由，执行如下命令：
    php artisan make:controller TaskController --resource
## 4、在web.php路由文件里面添加资源路由：
    Route::patch('task/{task}/check',['as'=>'task.check','uses'=>'TaskController@check']);
    
    Route::resource('task','TaskController');
## 5、修改ProjectController.php里面的show()方法，用来显示具体项目下的所有任务和添加任务，分为‘未完成’和‘已完成’：
    public function show($id)
    {
        $project = Auth::user()->projects()->where('id',$id)->first();//获取当前用户当前项目
        $projectList = Auth::user()->projects()->pluck('name','id');//获取当前用户项目的键值对数组
        $todo = $project->tasks()->where('completed','F')->get();//获取未完成任务
        $done = $project->tasks()->where('completed','T')->get();//获取已完成任务
        return view('projects.show',compact('project','projectList','todo','done'));
    }
## 6、修改TaskController.php里面的index()方法，用来显示所有任务，内容为：
    public function index()
    {
        $projectList = Auth::user()->projects()->pluck('name','id');
        $todo = Auth::user()->tasks()->where('completed','F')->paginate(15);
        $done = Auth::user()->tasks()->where('completed','T')->paginate(15);
        return view('tasks.index',compact('todo','done','projectList'));
    }
## 7、修改TaskController.php里面的store()方法，用来添加任务，内容为：
    public function store(Request $request)
    {
        Task::create([
            'name'=> $request->name,
            'project_id' => $request->id
        ]);
        return back();
    }
## 8、在TaskController.php里面创建check()方法，用来标记完成任务，内容为：
    public function check($id)
    {
        $task = Task::findOrFail($id);
        $task->completed = 'T';
        $task->save();
        return back();
    }
## 9、修改TaskController.php里面的store()方法，用来更新(编辑)任务，内容为：
    public function update(Request $request, $id)
    {
        $task = Task::findOrFail($id);
        $task->update([
            'name'=>$request->name,
            'project_id'=>$request->projectList
        ]);
        return back();
    }
## 10、修改TaskController.php里面的destroy()方法，用来删除任务，内容为：
    public function destroy($id)
    {
        Task::findOrFail($id)->delete();
        return back();
    }
## 11、将Carbon显示的时间设置为中文，可以在 app/Providers/AppServiceProvider.php 的 boot() 方法中添加下面的代码来设置全局本地化：
    public function boot()
    {
        \Carbon\Carbon::setLocale('zh');
    }
# 设置导航栏链接菜单：在导航栏添加对应的链接菜单，而且要判断是否是当前选中的状态来分别配置css样式，views\layouts\app.blade.php里面的左边导航里代码如下：
    <!-- Left Side Of Navbar -->
    <ul class="nav navbar-nav">
        <li class="{{ url()->current()==route('project.index')?'active':'' }}"><a href="{{ route('project.index') }}">Project</a></li>
        <li class="{{ url()->current()==route('task.index')?'active':'' }}"><a href="{{ route('task.index') }}">Task</a></li>
    </ul>
# 步骤五、实现表单数据验证功能：
## 1、实现项目添加表单的验证功能：
### ①创建一个createProjectRequest的验证文件，文件位于app\Http\Requests里面：
    php artisan make:request createProjectRequest
### ②修改createProjectRequest的内容为：
    <?php
    
    namespace App\Http\Requests;
    
    use Illuminate\Foundation\Http\FormRequest;
    
    class createProjectRequest extends FormRequest
    {    
        public function authorize()
        {
            return true;//这里需要改为true
        }
           
        public function rules()
        {
            return [
                'name' => 'required|unique:projects',//不能为空，且在projects表里面不能重复
                'thumbnail' => 'image|dimentions:min_width=261,min_height=98'//必须是图片,且图片大小最小为261*98
            ];
        }
    
        public function messages()//对应的错误提示信息
        {
            return [
                'name.required' => '项目名称不能为空',
                'name.unique' => '该项目名称已存在，请换一个名称',
                'thumbnail.image' => '缩略图只能是图片格式'
            ];
        }
    }
### ③进入ProjectController里面修改唯一一个地方即可实现验证功能：
    use App\Http\Requests\createProjectRequest;//这里不要忘记引用一下
    public function store(createProjectRequest $request)//这是唯一一处修改的地方，不过不要忘记use一下
    {
        $this->repo->createProject($request);
        return back();
    }
## 2、实现项目修改(编辑)表单的验证功能，可以直接用createProjectRequest：
### 进入ProjectController里面修改唯一一个地方即可实现验证功能：
    public function update(createProjectRequest $request, $id)//这是唯一一处修改的地方
    {
        $this->repo->updateProject($request,$id);
        return back();
    }
## 3、实现增加任务的表单验证功能：
### ①创建一个createTaskRequest的验证文件，文件位于app\Http\Requests里面：
        php artisan make:request createTaskRequest    
### ②修改createTaskRequest的内容为：    
    <?php
    namespace App\Http\Requests;
    
    use Illuminate\Foundation\Http\FormRequest;
    
    class createTaskRequest extends FormRequest
    {

        public function authorize()
        {
            return true;
        }

        public function rules()
        {
            return [
                'name' => 'required'
            ];
        }
    
        public function messages()
        {
            return [
                'name.required' => '任务名称不能为空'
            ];
        }
    }
### ③进入TaskController里面修改唯一一个地方即可实现验证功能：
    use App\Http\Requests\createTaskRequest;//不要忘记在上面引用一下
    public function store(createTaskRequest $request)//这是唯一一处修改的地方
    {
        Task::create([
            'name'=> $request->name,
            'project_id' => $request->id
        ]);
        return back();
    }
## 4、如果想要显示提示信息，需要在相应的位置加入如下代码：
    @if ($errors->has('name'))  //这里的'name'为需要验证的字段名，按需修改即可
        <span class="help-block alert-danger">
            <strong>{{ $errors->first('name') }}</strong>//这是只显示关于name字段的一条记录
        </span>
    @endif
    或显示多条记录：
    @if ($errors->has('name'))
        <span class="help-block alert-danger">
            @foreach($errors->get('name') as $error) //显示关于name字段的多条记录
                <li>{{ $error }}</li>  
            @endforeach
        </span>
    @endif
    或显示所有错误提示则用下面代码：
    @if($errors->any())
        <ul class = "alert alert-danger">
            @foreach($errors->all() as $error) //显示所有记录
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif
# 步骤六、实现view composer功能(即如何实现向一个公用的模块传递数据)：
## 1、在views\layouts目录下创建一个名为footer.blade.php的公用模板，用来作为网站的底部显示一些数据，内容为：
    <div class="footer" style="position: absolute;bottom: 0;height: 60px;line-height: 60px;width: 100%;background-color: #ffffff;">
        <div class="container">
            当前共有  个任务，已完成 个，未完成 个。
        </div>
    </div>
## 2、在layouts\app.blade.php中相应的地方引入该模板：
    @include('layouts.footer')
## 3、思考如何将任务总数及完成和未完成数据传递到footer.blade.php里面呢？
### 思考①、首先不可能到每个Controller里面写代码然后传递给每个视图；
### 思考②、如果直接显示为：当前共有{{$total}}个任务，已完成{{$doneCount}}个，未完成{{$todoCount}}个就完美了，但是有个问题就是要报错，因为找不到这些变量怎么解决？
### 思考③、如果在`系统启动之后`就能拿到$total这些变量，然后传递给layouts.footer视图那么就完美了
### ④、那么就考虑到在app\Providers\AppServiceProvider.php里面的boot()方法里写代码，然后传递给视图就可以了，具体代码如下：
### ⑤、也许需要传递数据的视图不只一个，所以我们也可以单独为view composer创建一个provider，执行如下命令：
    php artisan make:provider ComposerServiceProvider
### ⑤-1、创建好ComposerServiceProvider之后系统还是找不到他，需要到config\app.blade.php配置文件里面添加该provider进行注册：
    App\Providers\ComposerServiceProvider::class,
### ⑥、到创建好的ComposerServiceProvider里面的boot()方法里面创建一个view composer，内容为：
    方法一：
    view()->share('key','value');//这个适合数量比较少的情况
    方法二：
    view()->composer('layouts.footer',function($view){ //layouts.footer为需要传递给数据的视图
        $view->with('total',\App\Task::total())//这是一种情况，\App\Task::total()是Task Model里面的得到total数据的一个方法
    })；
    方法三：//为了规范方法二，可以如下实现最常用，推荐用：
    view()->composer('layouts.footer','App\Http\ViewComposers\TaskCountComposer@compose')//这就是用'App\Http\ViewComposers\TaskComposer@compose'代替上面的回调函数，同时需要创建TaskComposer及compose()方法，这个方法是默认的在这里可以不填的
### ⑥-1、然后创建App\Http\ViewComposers\TaskCountComposer，内容为：
    <?php
    namespace App\Http\ViewComposers;
    
    use Illuminate\View\View;
    
    class TaskCountComposer
    {
        public function compose(View $view)
        {
            $view->with([
                'total' => 20,
                'doneCount' => 10,
                'todoCount' => 10,
            ]);
        }
    }
### ⑥-2、上面一步的数据都是直接输入的，如何从数据库提取数据呢：
#### 首先、创建一个App\Repositories\taskRepository.php，内容为：
    <?php
    namespace App\Repositories;
    
    use App\Task;
    
    class taskRepository
    {
        public function total()
        {
            $total = Task::all()->count();
            return $total;
        }
    
        public function todoCount()
        {
            $todoCount = Task::where('completed','F')->count();
            return $todoCount;
        }
    
        public function doneCount()
        {
            $doneCount = Task::where('completed','T')->count();
            return $doneCount;
        }
    }
#### 其次、到App\Http\ViewComposers\TaskCountComposer引入taskRepository,引入之后TaskCountComposer.php的代码为：
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
# 步骤七、实现图表统计功能：
## 1、首先创建一个显示图表的视图文件，位于views\charts目录下面index.blade.php
## 2、在web.php路由文件里面为上面的视图分配一个路由：
    Route::get('chart','ChartController@index')->name('chart.index');
## 3、创建一个ChartController，执行如下命令：
    php artisan make:controller ChartController
## 4、ChartController内容为：
    <?php
    
    namespace App\Http\Controllers;
    
    use Illuminate\Http\Request;
    
    class ChartController extends Controller
    {
        public function index()
        {
            return view('charts.index');
        }
    }
## 5、导航条添加chart链接，指向charts\index.blade.php,即修改layouts\app.blade.php：
    <!-- Left Side Of Navbar -->
    <ul class="nav navbar-nav">
        <li class="{{ url()->current()==route('chart.index')?'active':'' }}"><a href="{{ route('chart.index') }}">Chart</a></li>
    </ul>
## 6、charts\index.blade.php的具体图表统计实现代码，我使用的是echarts参考文档在http://echarts.baidu.com/index.html：
    
    
    
    