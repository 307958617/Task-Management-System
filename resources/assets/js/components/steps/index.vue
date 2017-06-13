<template>
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <ul class="list-group">
                     <li class="list-group-item" v-for="(step, index) in todoSteps">
                         <span @dblclick="edit(step)">{{ step.name }}</span><!-- 这里注意，双击事件是dblclick -->
                         <i class="fa fa-close pull-right" @click="deleteStep(index)"></i>
                         <i class="fa fa-check pull-right" @click="completeStep(step)"></i>
                     </li>
                </ul>
                <input class="form-control" v-focus="focusStatus" type="text" @keyup.enter="addStep" v-model="newStep.name">

                <ul class="list-group">
                    <li class="list-group-item" v-for="(step, index) in doneSteps">
                        <span @dblclick="edit(step)">{{ step.name }}</span>
                        <i class="fa fa-close pull-right" @click="deleteStep(index)"></i>
                        <i class="fa fa-check pull-right" @click="completeStep(step)"></i>
                    </li>
                </ul>
            </div>
            {{ $data | json }}
        </div>
    </div>
</template>

<script>
    export default {
        mounted() {
            console.log('Component index.')
        },
        data() {
            return {
                steps:[
                    {name:'first',completed:false},
                    {name:'second',completed:true},
                    {name:'third',completed:false},
                ],
                newStep:{name:'',completed:''},
                focusStatus:true
            }
        },
        directives: {//自定义指令：focus 然后在input里面设置 v-focus="focusStatus"，focusStatus就是对应的{value}
            focus: {
                inserted: function (el,{value}) {
                    if (value) el.focus()
                }
            }
        },
        methods: {
            addStep() {
                this.steps.push(this.newStep);
                this.newStep = {name:'',completed:''}
            },
            deleteStep(index) {
                this.steps.splice(index,1)
            },
            completeStep(step) {
                step.completed = !step.completed
            },
            edit(step) {
                //双击删除当前step，这里需要注意的是不能直接传递index，因为已经在deleteStep()方法中用了index，会产生混淆
                var index = this.steps.indexOf(step);  //必须重新产生当前的index出可以
                this.deleteStep(index);
                //输入框中显示当前step的名称，因为当前输入框显示的就是newStep，因此只要给它赋值即可
                this.newStep.name = step.name;
                //输入框获得焦点
                //$('input').focus();//但是这样不是vue.js的内容
            },
        },
        computed: {
            todoSteps() { //列出所有步骤中未完成的步骤
               return this.steps.filter(function (step) {  //用filter来取出数组的每个元素来判断，接收一个回调函数
                    if (!step.completed) return step
                })
            },
            doneSteps() { //列出所有步骤中已完成的步骤
                return this.steps.filter(function (step) {  //用filter来取出数组的每个元素来判断，接收一个回调函数
                    if (step.completed) return step
                })
            }
        }
    }
</script>