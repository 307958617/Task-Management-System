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

        return $this->registered($request, $user)
                        ?: redirect($this->redirectPath());
    }
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