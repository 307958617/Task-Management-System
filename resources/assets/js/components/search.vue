<template>
    <div>
        <form class="navbar-form navbar-left" role="search">
            <div class="form-group">
                <div class="input-group">
                    <input type="text" v-model="searchString" @blur="unFocus" @focus="fetchTasks" class="form-control" placeholder="Search Tasks">
                    <div class="input-group-addon"><i class="fa fa-search"></i></div>
                </div>
                <ul class="list-group search">
                    <li class="list-group-item" v-for="task in searchForTasks">
                        <a :href="'/task/'+ task.id">
                            {{ task.name }}
                    </a>
                    </li>
                </ul>
            </div>
        </form>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                tasks:[],
                searchString:''
            }
        },
        computed:{
            searchForTasks(){
                return this.tasks.filter(function (task) {
                    return task.name.toLowerCase().indexOf(this.searchString.trim().toLowerCase()) !== -1 ;
                }.bind(this))
            }
        },
        methods:{
            fetchTasks() {
                axios('http://task.app/task/searchApi').then(response =>{
                    this.tasks = response.data
                })
            },
            unFocus() {
                setTimeout(function () {//设置延时执行的动作
                    this.tasks= [];
                }.bind(this),100)
            }
        }
    }
</script>