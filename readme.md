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
    
    use App\Repositories\projectRepository;
    use App\Repositories\taskRepository;
    use Illuminate\Http\Request;
    
    class ChartController extends Controller
    {
        protected $task,$project;
        public function __construct(taskRepository $task,projectRepository $project)
        {
            $this->task = $task;
            $this->project = $project;
        }
        public function index()
        {
            $taskTotal = $this->task->total();
            $todoCount = $this->task->todoCount();
            $doneCount = $this->task->doneCount();
            $projectTotal = $this->project->total();
            $projectNameList = $this->project->projectNameList();
            $projects = $this->project->projects();
            return view('charts.index',compact('taskTotal','todoCount','doneCount','projectTotal','projectNameList','projects'));
        }
    }
## 5、导航条添加chart链接，指向charts\index.blade.php,即修改layouts\app.blade.php：
    <!-- Left Side Of Navbar -->
    <ul class="nav navbar-nav">
        <li class="{{ url()->current()==route('chart.index')?'active':'' }}"><a href="{{ route('chart.index') }}">Chart</a></li>
    </ul>
## 6、charts\index.blade.php的具体图表统计实现代码，我使用的是echarts参考文档在http://echarts.baidu.com/index.html：
    @extends('layouts.app')
    @section('css')
        <!-- 通过cdn方式引入ECharts -->
        <script src="https://cdn.bootcss.com/echarts/3.6.1/echarts.js"></script>
    @endsection
    @section('content')
        <div class="container">
            <div class="col-md-4">
                <!-- 为 ECharts 柱状图 准备一个具备大小（宽高）的 DOM -->
                <div id="pieChart" style="width: 100%;height: 300px"></div>
            </div>
            <div class="col-md-4">
                <!-- 为 ECharts 柱状图 准备一个具备大小（宽高）的 DOM -->
                <div id="barChart" style="width: 100%;height: 300px"></div>
            </div>
        </div>
    @endsection
    @section('js')
        <script type="text/javascript">
            // 基于准备好的dom，初始化echarts实例
            var pieChart = echarts.init(document.getElementById('pieChart'));
    
            // 指定图表的配置项和数据
            var pieChartOption = {
                title : {
                    text: '任务完成量统计图',
                    subtext: '任务总数：'+ {{ $taskTotal }},
                    x:'center'
                },
                tooltip : {
                    trigger: 'item',
                    formatter: "{a} <br/>{b} : {c} ({d}%)"
                },
                legend: {
                    orient: 'horizontal',
                    left: 'center',
                    bottom:0,
                    data: ['未完成任务','已完成任务']
                },
                series : [
                    {
                        name: '任务数',
                        type: 'pie',
                        radius : '55%',
                        center: ['50%', '55%'],
                        data:[
                            {value:{{ $todoCount }}, name:'未完成任务'},
                            {value:{{ $doneCount }}, name:'已完成任务'}
                        ],
                        itemStyle: {
                            emphasis: {
                                shadowBlur: 10,
                                shadowOffsetX: 0,
                                shadowColor: 'rgba(0, 0, 0, 0.5)'
                            }
                        }
                    }
                ]
            };
            // 使用刚指定的配置项和数据显示图表。
            pieChart.setOption(pieChartOption);
    
    
    
            // 下面是柱状图
            var barChart = echarts.init(document.getElementById('barChart'));
            var barChartOption = {
                title : {
                    text: '项目种类及相关完成情况',
                    subtext: '项目总数：'+ {{ $projectTotal }},
                    x:'center'
                },
                tooltip : {
                    trigger: 'axis',
                    axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                        type : 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
                    }
                },
                legend: {
                    data:['任务总数','未完成','已完成'],
                    bottom:0
                },
                grid: {
                    left: '3%',
                    right: '4%',
                    bottom: '8%',
                    containLabel: true
                },
                xAxis : [
                    {
                        type : 'category',
                        data : {!! $projectNameList !!}//如果还是不显示，就用{!! json_encode($projectNameList,JSON_UNESCAPED_UNICODE) !!}
                    }
                ],
                yAxis : [
                    {
                        type : 'value'
                    }
                ],
                series : [
                    {
                        name:'任务总数',
                        type:'bar',
                        data:{!! json_encode(TaskCountArray($projects)) !!},//这里必须要用json_encode()转吗
                    },
                    {
                        name:'已完成',
                        type:'bar',
                        barWidth : 5,
                        stack: '任务总数',
                        data:{!! json_encode(DoneTaskCountArray($projects)) !!}
                    },
                    {
                        name:'未完成',
                        type:'bar',
                        barWidth : 5,
                        stack: '任务总数',
                        data:{!! json_encode(TodoTaskCountArray($projects)) !!}
                    }
                ]
            };
            barChart.setOption(barChartOption);
            
            
        </script>
    @endsection
## 6-1、有一个问题就是如何获得每个项目的任务数？好像是无从下手，请思考：
### 思考①、如果我们从需要的结果考虑入手，直接用{{ TaskCountArray($projects) }},就可以得到如下数组就好了[1,2]，但是又要如何实现呢？
### 思考②、那么TaskCountArray()这个函数放到哪里好呢，是不是需要全局比较好呢，那么就要用到helper全局函数来帮忙了
### 手动创建TaskCountArray()这个全局函数，步骤如下：
#### 1、在app目录下创建一个名为：Helper.php的帮助函数库，内容为：
    <?php
    
    function TaskCountArray($projects)
    {
        $projectTaskCountArray = [];
        foreach($projects as $project){
            $projectTaskCount = $project->tasks()->count();
            array_push($projectTaskCountArray,$projectTaskCount);
        }
        return $projectTaskCountArray;
    }
    
    function TodoTaskCountArray($projects)
    {
        $projectTaskCountArray = [];
        foreach($projects as $project){
            $projectTaskCount = $project->tasks()->where('completed','F')->count();
            array_push($projectTaskCountArray,$projectTaskCount);
        }
        return $projectTaskCountArray;
    }
    
    function DoneTaskCountArray($projects)
    {
        $projectTaskCountArray = [];
        foreach($projects as $project){
            $projectTaskCount = $project->tasks()->where('completed','T')->count();
            array_push($projectTaskCountArray,$projectTaskCount);
        }
        return $projectTaskCountArray;
    }
#### 2、如何使Laravel识别Helper.php里面的函数呢？到composer.json文件里的"autoload":{}里面添加如下代码：
    "autoload": {
        "classmap": [
            "database"
        ],
        "psr-4": {
            "App\\": "app/"
        },
        "files": [
            "app/Helper.php"
        ]
    },
#### 3、还需要在命令行执行如下代码刷新autoload缓存才能马上起作用：
    composer dumpautoload
## 7、如何重构上面6步的代码，将每个图形的js代码单独放到一个js文件里面，然后引用即可：
### 7-1,重构饼状图的js代码：
### ①、在public\js\目录下面创建一个pie.js,用来放置饼状图的js代码，内容为：
    // 基于准备好的dom，初始化echarts实例
    var pieChart = echarts.init(document.getElementById('pieChart'));
    
    // 指定图表的配置项和数据
    var pieChartOption = {
        title : {
            text: '任务完成量统计图',
            subtext: '任务总数：'+ {{ $taskTotal }},
            x:'center'
        },
        tooltip : {
            trigger: 'item',
            formatter: "{a} <br/>{b} : {c} ({d}%)"
        },
        legend: {
            orient: 'horizontal',
            left: 'center',
            bottom:0,
            data: ['未完成任务','已完成任务']
        },
        series : [
            {
                name: '任务数',
                type: 'pie',
                radius : '55%',
                center: ['50%', '55%'],
                data:[
                    {value:{{ $todoCount }}, name:'未完成任务'},
                    {value:{{ $doneCount }}, name:'已完成任务'}
                ],
                itemStyle: {
                    emphasis: {
                        shadowBlur: 10,
                        shadowOffsetX: 0,
                        shadowColor: 'rgba(0, 0, 0, 0.5)'
                    }
                }
            }
        ]
    };
    // 使用刚指定的配置项和数据显示图表。
    pieChart.setOption(pieChartOption);
### ②、但是现在又有个问题了，{{ $taskTotal }}这样的参数就不能直接传递进去了，只有用jquery的方式来传递：
#### 1、首先要添加如下代码到charts\index.blade.php里面来传递参数：
    <div id="pie-data" data-total="{{ $taskTotal }}" data-todo="{{ $todoCount }}" data-done="{{ $doneCount }}"></div>
#### 2、然后在public\js\pie.js里面用jquery的方式调用这些数据：
    将{{ $taskTotal }}换成$('#pie-data').data('total');
    将{{ $todoCount }}换成$('#pie-data').data('todo');
    将{{ $doneCount }}换成$('#pie-data').data('done');
#### 3、最终的public\js\pie.js代码为：
    // 基于准备好的dom，初始化echarts实例
    var pieChart = echarts.init(document.getElementById('pieChart'));
    
    // 指定图表的配置项和数据
    var pieChartOption = {
        title : {
            text: '任务完成量统计图',
            subtext: '任务总数：'+ $('#pie-data').data('total'),
            x:'center'
        },
        tooltip : {
            trigger: 'item',
            formatter: "{a} <br/>{b} : {c} ({d}%)"
        },
        legend: {
            orient: 'horizontal',
            left: 'center',
            bottom:0,
            data: ['未完成任务','已完成任务']
        },
        series : [
            {
                name: '任务数',
                type: 'pie',
                radius : '55%',
                center: ['50%', '55%'],
                data:[
                    {value:$('#pie-data').data('todo'), name:'未完成任务'},
                    {value:$('#pie-data').data('done'), name:'已完成任务'}
                ],
                itemStyle: {
                    emphasis: {
                        shadowBlur: 10,
                        shadowOffsetX: 0,
                        shadowColor: 'rgba(0, 0, 0, 0.5)'
                    }
                }
            }
        ]
    };
    // 使用刚指定的配置项和数据显示图表。
    pieChart.setOption(pieChartOption);
### 7-2,重构柱状图的js代码：    
### ①、同上面方法一样，在public\js\目录下面创建一个bar.js,用来放置柱状图的js代码，内容为：
    // 下面是柱状图
    var barChart = echarts.init(document.getElementById('barChart'));
    var barChartOption = {
        title : {
            text: '项目种类及相关完成情况',
            subtext: '项目总数：'+ {{ $projectTotal }},
            x:'center'
        },
        tooltip : {
            trigger: 'axis',
                axisPointer : {            // 坐标轴指示器，坐标轴触发有效
                type : 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
            }
        },
        legend: {
            data:['任务总数','未完成','已完成'],
                bottom:0
        },
        grid: {
            left: '3%',
                right: '4%',
                bottom: '8%',
                containLabel: true
        },
        xAxis : [
            {
                type : 'category',
                data : {!! $projectNameList !!}//如果还是不显示，就用{!! json_encode($projectNameList,JSON_UNESCAPED_UNICODE) !!}
            }
        ],
        yAxis : [
            {
                type : 'value'
            }
        ],
        series : [
            {
                name:'任务总数',
                type:'bar',
                data:{!! json_encode(TaskCountArray($projects)) !!},//这里必须要用json_encode()转吗
            },
            {
                name:'已完成',
                    type:'bar',
                barWidth : 5,
                stack: '任务总数',
                data:{!! json_encode(DoneTaskCountArray($projects)) !!}
            },
            {
                name:'未完成',
                    type:'bar',
                barWidth : 5,
                stack: '任务总数',
                data:{!! json_encode(TodoTaskCountArray($projects)) !!}
            }
        ]
    };
    barChart.setOption(barChartOption);
### ②、添加如下代码到charts\index.blade.php里面来传递参数：
    <div id="bar-data" 
         data-projecttotal={{ $projectTotal }} 
         data-projectnamelist={!! $projectNameList !!} 
         data-totalcount={!! json_encode(TaskCountArray($projects)) !!}
         data-donecount={!! json_encode(DoneTaskCountArray($projects)) !!}
         data-todocount={!! json_encode(TodoTaskCountArray($projects)) !!}
    ></div>
### ③、在public\js\bar.js里面用jquery的方式调用这些数据：
    将{{ $projectTotal }}换成：$('#bar-data').data('projecttotal');
    将{!! $projectNameList !!}换成：$('#bar-data').data('projectnamelist');
    将{!! json_encode(TaskCountArray($projects)) !!}换成：$('#bar-data').data('totalcount');
    将{!! json_encode(DoneTaskCountArray($projects)) !!}换成：$('#bar-data').data('donecount');
    将{!! json_encode(TodoTaskCountArray($projects)) !!}换成：$('#bar-data').data('todocount');
## 然后到charts\index.blade.php里面引用两个图表文件即可（就是这么简单）：
    @section('js')
        <script src="{{ asset('js/pie.js') }}"></script>
        <script src="{{ asset('js/bar.js') }}"></script>
    @endsection
## 8、但是为了减少http请求，最好是将pie.js和bar.js这两个文件编译到app.js里面，那么如何做呢：
### ①、将bar.js,pie.js文件剪切到resources\assets\js\charts目录下面
### ②、找到webpack.mix.js文件，对里面的内容进行如下编辑：
    mix.js('resources/assets/js/app.js', 'public/js')
        .js(['resources/assets/js/charts/bar.js','resources/assets/js/charts/pie.js'],'public/js/charts.js')
       .sass('resources/assets/sass/app.scss', 'public/css');
### ③、在命令行里面执行如下命令进行编译：
    npm run dev
### ④、然后到charts\index.blade.php里面调用一个文件就行了：
    @section('js')
        <script src="{{ asset('js/charts.js') }}"></script>
    @endsection
## 9、再创建一个雷达图，实现类似于bar.js的效果：
### ①、雷达图的步骤和上面是一样的，但是需要解决一个核心的问题，就是如何解决data的数据循环问题(即下面的data如何通过循环输出)：
    data : [
        {
            value : [4300, 10000, 28000],
            name : '私人任务'
        },
        {
            value : [5000, 15000, 24000],
            name : '余亭'
        },
        {
            value : [4000, 12000, 27000],
            name : '公共任务'
        },
        {
            value : [3000, 16000, 28000],
            name : 'yuting'
        }
    ]
### ②、解决上面的循环输出问题，即在js里面写php代码：
    data : [
        <?php
            $i = 0;
            foreach ($projects as $project):
                $name = $project->name;
                $totalPP = $project->tasks()->count();
                $todoPP = $project->tasks()->where('completed','F')->count();
                $donePP = $project->tasks()->where('completed','T')->count();
                echo '{';
        ?>
            value: [ <?php echo $totalPP.',' .$todoPP.',' .$donePP?> ],
            name: "<?php echo $name?>"
        <?php
                ($i+1) == $projects->count()? print '}':print '},';
                $i++;
            endforeach;
        ?>
    ]
### ③、在charts\index.blade.php里面创建放置雷达图DOM的代码为：
    <div class="col-md-4">
        <!-- 为 ECharts 雷达状图 准备一个具备大小（宽高）的 DOM -->
        <div id="radarChart" style="width: 100%;height: 300px"></div>
    </div>
### ④、雷达图的js代码，内容为：
    // 下面是雷达图
    var radarChart = echarts.init(document.getElementById('radarChart'));
    radarChartOption = {
        title: {
            text: '基础雷达图',
            x: 'center'
        },
        tooltip: {},
        legend: {
            data: {!! $projectNameList !!},
            bottom: 0
        },
        radar: {
            // shape: 'circle',
            indicator: [   //下面的max:是取得所有项目中任务数量最大的数值，getMax()是在Helper.php里面定义的
                { name: '项目总数', max: {{ getMax(TaskCountArray($projects)) }} },
                { name: '未完成', max:  {{ getMax(TaskCountArray($projects)) }} },
                { name: '已完成', max: {{ getMax(TaskCountArray($projects)) }} },
            ],
            center: ['50%','60%']
        },
        series: [{
            type: 'radar',
            areaStyle: {normal: {}},
            data : [
                <?php
                    $i = 0;
                    foreach ($projects as $project):
                    $name = $project->name;
                    $totalPP = $project->tasks()->count();
                    $todoPP = $project->tasks()->where('completed','F')->count();
                    $donePP = $project->tasks()->where('completed','T')->count();
                    echo '{';
                ?>
                    value: [ <?php echo $totalPP.',' .$todoPP.',' .$donePP?> ],
                    name: "<?php echo $name?>"
                <?php
                    ($i+1) == $projects->count()? print '}':print '},';
                    $i++;
                    endforeach;
                ?>
            ]
        }]
    };
    radarChart.setOption(radarChartOption);
### ⑤、Helper.php里面添加getMax($arr)方法，用来找出所有项目中任务数最大的数值：
    function getMax($arr)
    {
        $max=$arr[0];
        foreach($arr as $k=>$v){
          if($v>$max){
              $max=$v;
          }
        }
        return $max;
    }
### 思考，如何将②步在js里面写php转换成在php里面写js代码？？？(提示，需要借助Helper.php)
#### ①、Helper.php里面添加代码：    
    function data($projects)
    {
        $data = [];
        foreach ($projects as $project){
            $name = $project->name;
            $totalPP = $project->tasks()->count();
            $todoPP = $project->tasks()->where('completed','F')->count();
            $donePP = $project->tasks()->where('completed','T')->count();
            $date = '{"value":['.$totalPP.','.$todoPP.','.$donePP.'],"name":'.'"'.$name.'"}';//这里很重要的，注意格式
            array_push($data,json_decode($date,true));//为什么这么写参考http://www.cnblogs.com/xcxc/p/3729207.html
        }
        return $data;
    }
#### ②、将雷达图的js代码的data部分进行修改：
    //修改前的代码
    data : [
            {
                value : [4300, 10000, 28000],
                name : '私人任务'
            },
            {
                value : [5000, 15000, 24000],
                name : '余亭'
            },
            {
                value : [4000, 12000, 27000],
                name : '公共任务'
            },
            {
                value : [3000, 16000, 28000],
                name : 'yuting'
            }
        ]
    //修改后的代码：
    data : {!! json_encode(data($projects)) !!}
#### ③、在resources\assets\js\charts目录下创建radar.js,内容为：
    // 下面是雷达图
    var radarChart = echarts.init(document.getElementById('radarChart'));
    radarChartOption = {
        title: {
            text: '基础雷达图',
            x: 'center'
        },
        tooltip: {},
        legend: {
            data: $('#radar-data').data('projectnamelist'),
            bottom: 0
            },
            radar: {
                // shape: 'circle',
                indicator: [
                    { name: '项目总数', max: $('#radar-data').data('max') },
                    { name: '未完成', max:  $('#radar-data').data('max') },
                    { name: '已完成', max: $('#radar-data').data('max') },
                ],
                center: ['50%','60%']
            },
            series: [{
                type: 'radar',
                areaStyle: {normal: {}},
                data : $('#radar-data').data('data')
            }]
    };
    radarChart.setOption(radarChartOption);
#### ④、添加如下代码到charts\index.blade.php里面来为雷达图传递参数： 
    <div id="radar-data"
        data-projectnamelist={!! $projectNameList !!}
        data-max={{ getMax(TaskCountArray($projects)) }}
        data-data={!! json_encode(data($projects)) !!}
    ></div>
#### ⑤、到webpack.mix.js添加'resources/assets/js/charts/radar.js'，然后执行：
    npm run dev
# 步骤八、Vue.js学习
## 1、laravel默认已经在resources\assets\js\app.js里面加载了Vue,并在resources\assets\js\bootstrap.js里面默认加载了axios插件用来处理http请求。并且通过Vue.component('example', require('./components/Example.vue'));引入了Example.vue，现在就可以在任何页面去使用这个组件了，方法是直接在页面里引入<example></example>即可调用
## 2、通过新建、编辑、修改steps(即给每个任务(task)增加实现步骤(steps)的功能)这个例子来学习如何使用vue.js:
### ①、在views\tasks目录下创建show.blade.php用来实现steps的增删改查，具体内容为：
### ②、修改TaskController里面的show()方法，实现视图绑定：
    public function show($id)
    {
        return view('tasks.show');
    }
### ③、在resources\assets\js\components目录下创建一个steps文件夹，用来存放于steps有关的vue组件；
### ④、在resources\assets\js\components\steps目录下创建index.vue来实现steps的增删改查功能,vue.index的最终代码为：
    <template>
        <div class="container">
            <div class="row">
                <div class="col-md-12"><!--添加步骤-->
                    <div class="panel panel-primary">
                        <div class="panel-heading">
                            <div class="panel-title panel-danger">完成该任务(Task)需要哪些步骤？</div>
                            <input class="form-control" type="text" @keyup.enter="addStep" v-model="newStep" v-focus="focusStatus">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6"><!--显示未完成的步骤-->
                    <div class="panel panel-danger">
                        <div class="panel-heading">
                            <h1 class="panel-title">
                                待完成的步骤({{ todoSteps.length }})
                                <button @click="completeAll" class="btn btn-xs btn-success">完成所有</button>
                            </h1>
                        </div>
                        <div v-if="todoSteps.length" class="panel-body">
                            <ul class="list-group">
                                <li class="list-group-item" v-for="(step, index) in steps" v-if="!step.completed" @dblclick="edit(step)">
                                    <table class="table" style="margin-bottom: 0">
                                        <tr>
                                            <td>
                                                <span>{{ step.name }}</span><!-- 这里注意，双击事件是dblclick -->
                                            </td>
                                            <td>
                                                <input class="form-control" type="text" @keyup.esc="exit(step)" @keyup.enter="updateStep(step)" v-if="step.editStatus" v-model="step.name" v-focus="focusStatus">
                                            </td>
                                            <td>
                                         <span class="pull-right">
                                             <i class="fa fa-close pull-right" @click="remove(step)"></i>
                                             <i class="fa fa-check pull-right" @click="toggleComplete(step)"></i>
                                         </span>
                                            </td>
                                        </tr>
                                    </table>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6"><!--显示已完成的步骤-->
                    <div class="panel panel-success">
                        <div class="panel-heading">
                            <h1 class="panel-title">
                                已完成的步骤({{ doneSteps.length }})
                                <button @click="clearCompleted" class="btn btn-xs btn-danger">删除所有已完成</button>
                            </h1>
                        </div>
                        <div v-if="doneSteps.length" class="panel-body">
                            <ul class="list-group">
                                <li class="list-group-item" v-for="(step, index) in steps" v-if="step.completed" @dblclick="edit(step)">
                                    <table class="table" style="margin-bottom: 0">
                                        <tr>
                                            <td>
                                                <span>{{ step.name }}</span><!-- 这里注意，双击事件是dblclick -->
                                            </td>
                                            <td>
                                                <input class="form-control" type="text" @keyup.esc="exit(step)" @keyup.enter="updateStep(step)" v-if="step.editStatus" v-model="step.name" v-focus="focusStatus">
                                            </td>
                                            <td>
                                             <span class="pull-right">
                                                 <i class="fa fa-close pull-right" @click="remove(step)"></i>
                                                 <i class="fa fa-check pull-right" @click="toggleComplete(step)"></i>
                                             </span>
                                            </td>
                                        </tr>
                                    </table>
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
    
    <script>
        export default {
            data() {
                return {
                    steps:[
                        {name:'',completed:false,editStatus:false}
                    ],
                    newStep:'',
                    focusStatus:false,//为用来操作input修改框是否获取焦点的状态做准备。
                    oldName:'',//用来记录修改前的step的名字，以便点击esc取消时还原原来的数据。
                    baseUrl: top.location + '/step/' //top.location是jquery的获取当前浏览器url的命令
                }
            },
            computed: {
                todoSteps() {//获取未完成步骤
                    return this.steps.filter(function (step) {
                        return !step.completed
                    })
                },
                doneSteps() {//获取已完成步骤
                    return this.steps.filter(function (step) {
                        return step.completed
                    })
                }
            },
            mounted() {//一加载就提取数据
                this.fetchSteps();
            },
            methods: {
                edit(step) {//实现双击后的显示input修改框，并且将当前的name写入input框，并获得焦点。
                    this.steps.filter(function (step) {
                        return step.editStatus = false;
                    });
                    step.editStatus = true;
                    this.focusStatus = true;
                    this.oldName = step.name;
                },
                updateStep(step) {//实现回车后将修改数据提交给数据库保存，并让input输入框消失。（使用axios.put也行）
                    axios.patch(this.baseUrl+ step.id,{name:step.name}).then(function (response) {
                        console.log(response);
                        step.editStatus = false;
                        this.focusStatus = false;
                    }.bind(this));
                },
                exit(step) {//实现点击esc退出当前的修改框即放弃修改
                    step.editStatus = false;
                    this.focusStatus = false;
                    step.name = this.oldName;
                },
                addStep() {//实现点击回车，添加数据到数据库
                    axios.post(this.baseUrl,{name:this.newStep}).then(function (response) {
                        //this.steps.push({name:this.newStep,completed:false,editStatus:false});//注意，这里不能用这样，因为如果这样的话没有加载新的数据，在更新的时候回报错而更新不了
                        this.newStep = '';
                        this.fetchSteps();//需要使用它来重新加载一下数据
                    }.bind(this));
                },
                fetchSteps() {//从数据库中获取steps的数据
                    axios(this.baseUrl).then(function(response){
                        this.steps = response.data;
                    }.bind(this))
                },
                remove(step) {
                    var index = this.steps.indexOf(step);
                    axios.delete(this.baseUrl+ step.id).then(function(response){
                        this.steps.splice(index,1);
                    }.bind(this))
                },
                toggleComplete(step) {
                    axios.patch(this.baseUrl + step.id +'/toggleComplete').then(function (response) {
                        step.completed = !step.completed;
                    });
                },
                completeAll() { //标记完成所有任务
                    axios.post(this.baseUrl +'complete').then(function (response) {
                        this.steps.forEach(function (step) {  //标记完成所有任务用forEach来解决，但是也可以用this.fetchSteps();来重新提取数据，因为在数据库里面已经改变过来了
                            step.completed = true;
                        })
                    }.bind(this));
                },
                clearCompleted() {
    //                this.steps.forEach(function (step) {  //这个方法不太好，因为请求太多次了，而且容易出现错误
    //                    if (step.completed) this.remove(step)
    //                }.bind(this))
    
                    axios.post(this.baseUrl +'clear').then(function (response) {
                        this.fetchSteps();
                    }.bind(this))
                }
            },
            directives: {
                focus: { //这里与的focus与input里面的v-focus对应
                    inserted:function (el,{value}) { //这里的value就是input里面v-focus='step.focusStatus'的focusStatus对应，同时这里要用update也要注意
                        if (value) el.focus()  //判断focusStatus是否为true，是就获得了焦点
                    }
                }
            }
        }
    </script>
### ⑤、到app.js里面注册刚才创建的index.vue，通过如下代码实现，以便show.blade.php调用：
    Vue.component('steps', require('./components/steps/index.vue'));
### ⑥、到show.blade.php里面调用index.vue，即只要在需要的位置添加如下代码即可实现：
    <steps></steps>
### ⑦、在调用之前，不要忘记执行如下代码进行编译，不然是调用不到的哦~！：
    npm run dev 或 npm run watch(如果这个检测不到，就需要用到npm run watch-poll)
## 3、Vue.js要点札记：
### ①、想要获得数组里面的部分有值，一般需要用到过滤器，但是2.0以后对过滤器进行了限制，推荐使用computed计算属性：
    例子：数据为steps，那么如何循环输出steps里面completed=false的step：
    data() {
        return {
            steps:[
                {name:'first',completed:false},
                {name:'second',completed:true},
                {name:'third',completed:false},
            ]    
        }
    },
    实现：
    computed: {  //这里采用计算属性来解决
        todoSteps() { //列出所有步骤中未完成的步骤
           return this.steps.filter(function (step) {  //用filter来取出数组的每个元素来判断，接收一个回调函数
                if (!step.completed) return step  //对每个step进行计算后返回符合条件的step
            })
        }
    }
    <li class="list-group-item" v-for="(step, index) in todoSteps"> //但是这是有问题的，如果想点击去删除name:'third'，却是删除的name:'second'
         {{ step.name }}
    </li>
### ①-1、但是还有一种方法是v-for与v-if同时使用，v-for 的优先级比 v-if 更高，这意味着 v-if 将分别重复运行于每个 v-for 循环中(推荐用此方法)：
    <li class="list-group-item" v-for="(step, index) in steps" v-if="!step.completed">
         {{ step.name }}
    </li>
### ②、实现双击列表li时，input的值为当前列的step.name,并且获得焦点：
#### 首先：在data()里面添加如下代码：
    focusStatus:false //添加一个是否获取焦点的状态参数focusStatus，默认为没有获得焦点
#### 其次：在li里面添加如下代码：
    @dblclick="edit(step)"//表示双击修改，这里需要注意是dblclick，不是dbclick
#### 然后：在methods里面写edit(step)方法：
    edit(step) {
        //实现双击列表实现删除当前列的step，即从steps里面删除，这里没有index,所以需要找到step对应的index才能删除
        var index = this.steps.indexOf(step);
        this.remove(index);
        //将newStep.name赋值为当前step的name
        this.newStep.name = step.name;
        //input获得焦点：
        //$('input').focus();//这是jquery的模式，在vue里面最好是换一种方式实现。
        this.focusStatus=true;//是否获取焦点的状态参数focusStatus为true，就表示获得焦点了
    }
#### 再然后：自定义指令v-focus:
    directives: {
        focus: { //这里与的focus与input里面的v-focus对应
            update:function (el,{value}) { //这里的value就是input里面v-focus='focusStatus'的focusStatus对应，同时这里要用update也要注意
                if (value) el.focus()  //判断focusStatus是否为true，是就获得了焦点
            }
        }
    }
#### 最后：在input里面添加如下代码：
    v-focus="focusStatus"
### ③、通过axios实现与数据库的交互--获取所有steps：
#### 首先创建Step Model 及 steps表，执行如下命令：
    php artisan make:model Step -m
#### 修改生成的steps表蓝图文件，内容为(不要忘记给steps表添加数据哦！)：
    public function up()
    {
        Schema::create('steps', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('task_id');
            $table->string('name');
            $table->boolean('completed')->default(false);
            $table->timestamps();
        });
    }
#### Step Model的内容为：
    class Step extends Model
    {
        protected $fillable = ['name','completed'];
    
        public function Task()
        {
            return $this->belongsTo('App\Task');
        }
    }
#### 同时需要到Task Model里面添加如下内容：
    public function steps()
    {
        return $this->hasMany('App\Step');
    }
#### 执行如下命令，将steps表写入数据库中：
    php artisan migrate
#### 生成StepController,并同时生成资源路由：
    php artisan make:controller StepController --resource
#### 到web.php路由文件注册路由：
    Route::resource('task.step','StepController');//注意这里是用了双重resource路由'task.step'，rul的格式就是：task/{task}/step/{step}
#### 进入StepController修改index()方法：
    public function index($id)
    {
        $steps = Task::findOrFail($id)->steps;//注意，这里是steps不是steps();
        return $steps;//这里我们直接返回数据，不需要返回视图
    }
#### 但是这时访问task.app/task/1/step会发现，completed字段的值显示为0，而不是我们所需要的false实现方法是在Step Model里面添加如下代码：
    public function getCompletedAttribute($value)
    {
        if ($value) return $this->completed = true;
        return $this->completed = false;
    }
#### 现在数据准备好了，那么就到index.vue文件里面讲steps数据改为：
    原数据：
    steps:[
        {name:'first',completed:false,editStatus:false},
        {name:'second',completed:false,editStatus:false},
        {name:'third',completed:true,editStatus:false},
        {name:'fourth',completed:false,editStatus:false},
    ],
    改为：
    steps：[{name:'',completed:false,editStatus:false}]//只保留一个结构即可。
#### 添加mounted()：
    mounted() {
        this.fetchSteps();
    },
#### 在methods里添加fetchSteps()方法：
    fetchSteps() {//从数据库中获取steps的数据
        axios('/task/1/step').then(function(response){  //'/task/1/step'这就是上面获取数据的地址
            this.steps = response.data;
        }.bind(this))  //这里一定要绑定this不然要报错找不到steps。
    }
### ④、通过axios实现与数据库的交互--添加新的step： 
#### 首先修改index.vue的addStep()方法：
    addStep() {
        axios.post('/task/1/step',{name:this.newStep}).then(function (response) {
            this.steps.push({name:this.newStep,completed:false,editStatus:false});
            this.newStep = '';
        }.bind(this));
    },
#### 修改StepController.php的store()方法：
    public function store($id,Request $request)
    {
        Task::findOrFail($id)->steps()->create([
            'name'=>$request->name
        ]);
    }
### 其他方法也是一样的步骤。
### 那么最后step相关的路由代码为：
    Route::resource('task.step','StepController');//注意这里是用了双重resource路由'task.step'，rul的格式就是：task/{task}/step/{step}
    Route::patch('task/{task}/step/{step}/toggleComplete','StepController@toggleComplete');//给完成步骤和取消完成步骤添加路由，因为resource路由没有这个方法
    Route::post('task/{task}/step/complete','StepController@completeAll');//给完成所有步骤添加路由，因为resource路由没有这个方法
    Route::post('task/{task}/step/clear','StepController@clearCompleted');//给清除所有已完成的步骤添加路由，因为resource路由没有这个方法
### 最后StepController的代码为：
    <?php
  
    namespace App\Http\Controllers;
    
    use App\Step;
    use App\Task;
    use Illuminate\Http\Request;
    
    class StepController extends Controller
    {
        public function index($id)
        {
            $steps = Task::findOrFail($id)->steps;
            return $steps;
        }

        public function store($id,Request $request)
        {
            Task::findOrFail($id)->steps()->create([
                'name'=>$request->name
            ]);
        }
        
        public function update($taskID,Request $request, $id)
        {
            $step = Step::findOrFail($id);
            $step->update([
                'name' => $request->name
            ]);
        }
    
        public function destroy($taskID,$id)
        {
            Step::findOrFail($id)->delete();
        }
    
        public function toggleComplete($taskID,$id)
        {
            $step = Step::findOrFail($id);
            $step->update([
                'completed' => !$step->completed
            ]);
        }
    
        public function completeAll($taskID)
        {
            Task::findOrFail($taskID)->steps()->update([
                'completed' => 1
            ]);
        }
    
        public function clearCompleted($taskID)
        {
            Task::findOrFail($taskID)->steps()->where('completed',1)->delete();
        }
    }
# 步骤九、实现在vue.js引入animate.css动画效果：
## 到tasks\show.blade.php里面引入animate.css的CDN
    @section('css')
        <link href="https://cdn.bootcss.com/animate.css/3.5.2/animate.css" rel="stylesheet">
    @endsection
## 然后到index.vue里面的相应位置添加如下代码，当然前面的class必须加入：class="animated"：
    
    :class="[step.completed?'fadeInRight':'']"
    或
    :class="{'fadeInRight':step.completed}"
# 步骤十、实现类似百度检索的功能，实现搜索站内资源的效果（以收索task为例）：
## 1、在导航栏上添加搜索框代码如下：
    <div>
        <form class="navbar-form navbar-left" role="search">
            <div class="form-group">
                <div class="input-group">
                    <input type="text" class="form-control" placeholder="Search">
                    <div class="input-group-addon"><i class="fa fa-search"></i></div>
                </div>
            </div>
        </form>
    </div>
## 2、到web.php路由文件里面定义一条返回所有Task数据的路由：
    Route::get('task/searchApi','TaskController@searchApi')->name('task.searchApi');//注意，这条路由需要放到Route::resource('task','TaskController');的前面，否则报错                                                                                               
## 3、到TaskController里面添加searchApi()方法如下：
    public function searchApi()
    {
        return Auth::user()->tasks;
    }
## 4、如何通过vue.js实现搜索的效果，那吗就考虑到使用vue组件，将第1步里面的代码放到vue组件里面，然后在vue里面写具体实现过程：
### ①、在assets\js\components里面创建search.vue文件，内容为：
    
### ②、到assets\js\app.js里面注册一个search组件：
    Vue.component('search', require('./components/search.vue'));
### ③、将第1步里面的代码换成如下：
    <search></search>
### ④、实现输入框获取焦点就弹出所有tasks的一个列表，为了解决弹出的列表将导航条撑开，给导航条设置一个固定高度即可
    到assets\sass\styles.scss里面添加如下代码，然后编译即可：
    .navbar-default{
      height: 50px;
    }
### ⑤、但是这里又出来一个问题，导航条没撑开，但是将内容撑开了，怎么办？列表太长了怎么办？
    只要将列表框设置一个固定高度即可：
    到assets\sass\styles.scss里面添加如下代码，然后编译即可：
    ul.list-group.search {
      height: 30em;//解决因为task内容太多而列表太长的问题
      overflow: auto;//显示一个滚动条出来
      position: absolute;//解决将内容撑开了的问题
    }
### ⑥、使列表框失去焦点时自动消失：
    首先：在input中添加一个失去焦点的事件触发：@blur="unFocus"
    然后在methods方法里面添加：
    unFocus() {
        setTimeout(function () {//设置延时执行的动作
            this.tasks= [];
        }.bind(this),1000)
    }
### ⑦、注意，这里面有个坑，就是vue2.0里面动态链接的写法必须如下才可以：
    <a :href="'/task/'+ task.id">  //不能直接<a href="/task/{{ task.id }}">
        {{ task.name }}.{{task.id}}
    </a>
### ⑧、实现在输入框输入数据过滤弹出的列表里面内容的方法，只要写一个computed即可：
    首先：在input里面绑定输入框 v-model="searchString"
    然后：v-for需要改变一下
    v-for="task in searchForTasks"
    其次：写computed计算属性
    computed:{
        searchForTasks(){
            return this.tasks.filter(function (task) {
                return task.name.toLowerCase().indexOf(this.searchString.trim().toLowerCase()) !== -1 ;
            }.bind(this))
        }
    },
