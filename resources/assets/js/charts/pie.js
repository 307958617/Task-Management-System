// 基于准备好的dom，初始化echarts实例
var pieChart = echarts.init(document.getElementById('pieChart'));

// 指定图表的配置项和数据
var pieChartOption = {
    title : {
        text: '任务完成量统计图',
        subtext: '任务总数：'+ $('#pie-data').data('total'),
        x:'center'
    },
    tooltip : {
        trigger: 'item',
        formatter: "{a} <br/>{b} : {c} ({d}%)"
    },
    legend: {
        orient: 'horizontal',
        left: 'center',
        bottom:0,
        data: ['未完成任务','已完成任务']
    },
    series : [
        {
            name: '任务数',
            type: 'pie',
            radius : '55%',
            center: ['50%', '55%'],
            data:[
                {value:$('#pie-data').data('todo'), name:'未完成任务'},
                {value:$('#pie-data').data('done'), name:'已完成任务'}
            ],
            itemStyle: {
                emphasis: {
                    shadowBlur: 10,
                    shadowOffsetX: 0,
                    shadowColor: 'rgba(0, 0, 0, 0.5)'
                }
            }
        }
    ]
};
// 使用刚指定的配置项和数据显示图表。
pieChart.setOption(pieChartOption);