<template>
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <ul class="list-group">
                     <li class="list-group-item" v-for="(step, index) in steps" v-if="!step.completed" @dblclick="edit(step)" :key="step.id">
                         <span>{{ step.name }}</span><!-- 这里注意，双击事件是dblclick -->
                         <i class="fa fa-close pull-right" @click="remove(index)"></i>
                         <i class="fa fa-check pull-right" ></i>
                     </li>
                </ul>
                <input class="form-control" type="text" @keyup.enter="addStep" v-model="newStep.name" v-focus="focusStatus">
                <ul class="list-group">
                    <li class="list-group-item" v-for="(step, index) in steps" v-if="step.completed">
                        <span>{{ step.name }}</span><!-- 这里注意，双击事件是dblclick -->
                        <i class="fa fa-close pull-right" @click="remove(index)"></i>
                        <i class="fa fa-check pull-right" ></i>
                    </li>
                </ul>
            </div>
            {{ $data | json }}
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                steps:[
                    {name:'first',completed:false},
                    {name:'second',completed:true},
                    {name:'third',completed:false},
                ],
                newStep:{name:'',completed:false},
                focusStatus:false //添加一个是否获取焦点的状态参数focusStatus，默认为没有获得焦点
            }
        },
        methods: {
            addStep() {
                this.steps.push(this.newStep);
                this.newStep={name:'',completed:false}
            },
            remove(index) {
                this.steps.splice(index,1)
            },
            edit(step) {
                //实现双击列表实现删除当前列的step，即从steps里面删除，这里没有index,所以需要找到step对应的index才能删除
                var index = this.steps.indexOf(step);
                this.remove(index);
                //将newStep.name赋值为当前step的name
                this.newStep.name = step.name;
                //input获得焦点：
//                $('input').focus();//这是jquery的模式，在vue里面最好是换一种方式实现。
                this.focusStatus=true;//是否获取焦点的状态参数focusStatus为true，就表示获得焦点了
            }
        },
        directives: {
            focus: { //这里与的focus与input里面的v-focus对应
                update:function (el,{value}) { //这里的value就是input里面v-focus='focusStatus'的focusStatus对应，同时这里要用update也要注意
                    if (value) el.focus()  //判断focusStatus是否为true，是就获得了焦点
                }
            }
        }
    }
</script>