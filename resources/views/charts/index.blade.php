@extends('layouts.app')
@section('css')
    <!-- 通过cdn方式引入ECharts -->
    <script src="https://cdn.bootcss.com/echarts/3.6.1/echarts.js"></script>
@endsection
@section('content')
    <div class="container">
        <div class="col-md-4">
            <!-- 为 ECharts 柱状图 准备一个具备大小（宽高）的 DOM -->
            <div id="barChart" style="width: 380px;height: 300px"></div>
        </div>
    </div>
@endsection
@section('js')
    <script type="text/javascript">
        // 基于准备好的dom，初始化echarts实例
        var barChart = echarts.init(document.getElementById('barChart'));

        // 指定图表的配置项和数据
        var barChartOption = {
            title: {
                text: '纺织品销量对比图',//标题
                left: 'center'//水平对齐方式
            },
            toolbox:{
                show:true,
                feature: {
                    dataView: {readOnly: false},
                    saveAsImage: {}
                }
            },
            tooltip: {},//提示框组件配置项
            legend: {  //图例组件配置项
                data:['任务数'],//图例项的名称
                bottom: 0 //图例组件离容器下侧的距离
            },
            xAxis: {
                data: ["总任务数","未完成","未完成","未完成","未完成","未完成","未完成","已完成"],
                axisLabel: {  //刻度标签配置项
                    rotate: 60,   //标签的旋转角度
                    interval: 0  //强制显示所有标签
                }
            },
            yAxis: {},
            series: [{
                name: '任务数',
                type: 'bar',
                data: [10, 5,5,6,5,7,0, 2]
            }]
        };

        // 使用刚指定的配置项和数据显示图表。
        barChart.setOption(barChartOption);
    </script>
@endsection