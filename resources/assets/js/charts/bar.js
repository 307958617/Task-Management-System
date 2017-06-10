// 下面是柱状图
var barChart = echarts.init(document.getElementById('barChart'));
var barChartOption = {
    title : {
        text: '项目种类及相关完成情况',
        subtext: '项目总数：'+ $('#bar-data').data('projecttotal'),
        x:'center'
    },
    tooltip : {
        trigger: 'axis',
            axisPointer : {            // 坐标轴指示器，坐标轴触发有效
            type : 'shadow'        // 默认为直线，可选为：'line' | 'shadow'
        }
    },
    legend: {
        data:['任务总数','未完成','已完成'],
            bottom:0
    },
    grid: {
        left: '3%',
            right: '4%',
            bottom: '8%',
            containLabel: true
    },
    xAxis : [
        {
            type : 'category',
            data : $('#bar-data').data('projectnamelist')//如果还是不显示，就用{!! json_encode($projectNameList,JSON_UNESCAPED_UNICODE) !!}
        }
    ],
    yAxis : [
        {
            type : 'value'
        }
    ],
    series : [
        {
            name:'任务总数',
            type:'bar',
            data:$('#bar-data').data('totalcount'),//这里必须要用json_encode()转吗
        },
        {
            name:'已完成',
                type:'bar',
            barWidth : 5,
            stack: '任务总数',
            data:$('#bar-data').data('donecount')
        },
        {
            name:'未完成',
                type:'bar',
            barWidth : 5,
            stack: '任务总数',
            data:$('#bar-data').data('todocount')
        }
    ]
};
barChart.setOption(barChartOption);