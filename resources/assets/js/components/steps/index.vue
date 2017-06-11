<template>
    <div class="container">
        <div class="row">
            <div class="col-md-8 col-md-offset-2">
                <ul class="list-group">
                     <li class="list-group-item">每年更新成本</li>
                </ul>
                <input class="form-control" type="text" v-model="message">
                <button v-on:click="warn('Form cannot be submitted yet.', $event)">Submits</button>
                <span>{{ reversedMessage }}</span>
            </div>
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
                message:''
            }
        },
        methods: {
            warn: function (message, event) {
                // 现在我们可以访问原生事件对象
                //if (event) event.preventDefault();
                alert(message)
            }
        },
        computed: {
            // a computed getter
            reversedMessage: function () {
                // `this` points to the vm instance
                return this.message.split('').reverse().join('')
            }
        }
    }
</script>