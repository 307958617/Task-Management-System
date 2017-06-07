<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class createProjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;//这里需要改为true
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|unique:projects',//不能为空，且在projects表里面不能重复
            'thumbnail' => 'image'//必须是图片
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
