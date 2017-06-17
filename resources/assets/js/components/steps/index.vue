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
                            <li class="list-group-item animated" :class="[!step.completed?'fadeInRight':'']" v-for="(step, index) in steps" v-if="!step.completed" @dblclick="edit(step)">
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
                            <li v-for="(step, index) in steps" class="list-group-item animated" :class="[step.completed?' fadeInLeft':'']" v-if="step.completed" @dblclick="edit(step)">
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

<style>

</style>