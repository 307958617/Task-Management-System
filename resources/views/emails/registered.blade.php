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
