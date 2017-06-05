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
