<template>
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <ul class="list-group">
                     <li class="list-group-item" v-for="(step, index) in steps">
                         {{ todoStep }}
                         <i class="fa fa-close pull-right" @click="deleteStep(index)"></i>
                         <i class="fa fa-check pull-right" @click="completeStep(step)"></i>
                     </li>
                </ul>
                <input class="form-control" type="text" @keyup.enter="addStep" v-model="newStep">
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
                newStep:''
            }
        },
        methods: {
            addStep() {
                this.steps.push({name:this.newStep,completed:false});
                this.newStep=''
            },
            deleteStep(index) {
                this.steps.splice(index,1)
            },
            completeStep(step) {
                step.completed = !step.completed
            }
        },
        computed: {
            todoStep() {
                return '1'
            }
        }
    }
</script>