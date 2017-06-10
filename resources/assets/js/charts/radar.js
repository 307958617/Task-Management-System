// 下面是雷达图
var radarChart = echarts.init(document.getElementById('radarChart'));
radarChartOption = {
    title: {
        text: '基础雷达图',
        x: 'center'
    },
    tooltip: {},
    legend: {
        data: $('#radar-data').data('projectnamelist'),
        bottom: 0
        },
        radar: {
            // shape: 'circle',
            indicator: [
                { name: '任务总数', max: $('#radar-data').data('max') },
                { name: '未完成', max:  $('#radar-data').data('max') },
                { name: '已完成', max: $('#radar-data').data('max') },
            ],
            center: ['50%','60%']
        },
        series: [{
            type: 'radar',
            areaStyle: {normal: {}},
            data : $('#radar-data').data('data')
        }]
};
radarChart.setOption(radarChartOption);