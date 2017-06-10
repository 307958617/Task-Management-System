/******/ (function(modules) { // webpackBootstrap
/******/ 	// The module cache
/******/ 	var installedModules = {};
/******/
/******/ 	// The require function
/******/ 	function __webpack_require__(moduleId) {
/******/
/******/ 		// Check if module is in cache
/******/ 		if(installedModules[moduleId])
/******/ 			return installedModules[moduleId].exports;
/******/
/******/ 		// Create a new module (and put it into the cache)
/******/ 		var module = installedModules[moduleId] = {
/******/ 			i: moduleId,
/******/ 			l: false,
/******/ 			exports: {}
/******/ 		};
/******/
/******/ 		// Execute the module function
/******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);
/******/
/******/ 		// Flag the module as loaded
/******/ 		module.l = true;
/******/
/******/ 		// Return the exports of the module
/******/ 		return module.exports;
/******/ 	}
/******/
/******/
/******/ 	// expose the modules object (__webpack_modules__)
/******/ 	__webpack_require__.m = modules;
/******/
/******/ 	// expose the module cache
/******/ 	__webpack_require__.c = installedModules;
/******/
/******/ 	// identity function for calling harmony imports with the correct context
/******/ 	__webpack_require__.i = function(value) { return value; };
/******/
/******/ 	// define getter function for harmony exports
/******/ 	__webpack_require__.d = function(exports, name, getter) {
/******/ 		if(!__webpack_require__.o(exports, name)) {
/******/ 			Object.defineProperty(exports, name, {
/******/ 				configurable: false,
/******/ 				enumerable: true,
/******/ 				get: getter
/******/ 			});
/******/ 		}
/******/ 	};
/******/
/******/ 	// getDefaultExport function for compatibility with non-harmony modules
/******/ 	__webpack_require__.n = function(module) {
/******/ 		var getter = module && module.__esModule ?
/******/ 			function getDefault() { return module['default']; } :
/******/ 			function getModuleExports() { return module; };
/******/ 		__webpack_require__.d(getter, 'a', getter);
/******/ 		return getter;
/******/ 	};
/******/
/******/ 	// Object.prototype.hasOwnProperty.call
/******/ 	__webpack_require__.o = function(object, property) { return Object.prototype.hasOwnProperty.call(object, property); };
/******/
/******/ 	// __webpack_public_path__
/******/ 	__webpack_require__.p = "";
/******/
/******/ 	// Load entry module and return exports
/******/ 	return __webpack_require__(__webpack_require__.s = 43);
/******/ })
/************************************************************************/
/******/ ({

/***/ 10:
/***/ (function(module, exports) {

// 基于准备好的dom，初始化echarts实例
var pieChart = echarts.init(document.getElementById('pieChart'));

// 指定图表的配置项和数据
var pieChartOption = {
    title: {
        text: '任务完成量统计图',
        subtext: '任务总数：' + $('#pie-data').data('total'),
        x: 'center'
    },
    tooltip: {
        trigger: 'item',
        formatter: "{a} <br/>{b} : {c} ({d}%)"
    },
    legend: {
        orient: 'horizontal',
        left: 'center',
        bottom: 0,
        data: ['未完成任务', '已完成任务']
    },
    series: [{
        name: '任务数',
        type: 'pie',
        radius: '55%',
        center: ['50%', '55%'],
        data: [{ value: $('#pie-data').data('todo'), name: '未完成任务' }, { value: $('#pie-data').data('done'), name: '已完成任务' }],
        itemStyle: {
            emphasis: {
                shadowBlur: 10,
                shadowOffsetX: 0,
                shadowColor: 'rgba(0, 0, 0, 0.5)'
            }
        }
    }]
};
// 使用刚指定的配置项和数据显示图表。
pieChart.setOption(pieChartOption);

/***/ }),

/***/ 11:
/***/ (function(module, exports) {

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
        indicator: [{ name: '任务总数', max: $('#radar-data').data('max') }, { name: '未完成', max: $('#radar-data').data('max') }, { name: '已完成', max: $('#radar-data').data('max') }],
        center: ['50%', '60%']
    },
    series: [{
        type: 'radar',
        areaStyle: { normal: {} },
        data: $('#radar-data').data('data')
    }]
};
radarChart.setOption(radarChartOption);

/***/ }),

/***/ 43:
/***/ (function(module, exports, __webpack_require__) {

__webpack_require__(9);
__webpack_require__(10);
module.exports = __webpack_require__(11);


/***/ }),

/***/ 9:
/***/ (function(module, exports) {

// 下面是柱状图
var barChart = echarts.init(document.getElementById('barChart'));
var barChartOption = {
    title: {
        text: '项目种类及相关完成情况',
        subtext: '项目总数：' + $('#bar-data').data('projecttotal'),
        x: 'center'
    },
    tooltip: {
        trigger: 'axis',
        axisPointer: { // 坐标轴指示器，坐标轴触发有效
            type: 'shadow' // 默认为直线，可选为：'line' | 'shadow'
        }
    },
    legend: {
        data: ['任务总数', '未完成', '已完成'],
        bottom: 0
    },
    grid: {
        left: '3%',
        right: '4%',
        bottom: '8%',
        containLabel: true
    },
    xAxis: [{
        type: 'category',
        data: $('#bar-data').data('projectnamelist' //如果还是不显示，就用{!! json_encode($projectNameList,JSON_UNESCAPED_UNICODE) !!}
        ) }],
    yAxis: [{
        type: 'value'
    }],
    series: [{
        name: '任务总数',
        type: 'bar',
        data: $('#bar-data').data('totalcount') //这里必须要用json_encode()转吗
    }, {
        name: '已完成',
        type: 'bar',
        barWidth: 5,
        stack: '任务总数',
        data: $('#bar-data').data('donecount')
    }, {
        name: '未完成',
        type: 'bar',
        barWidth: 5,
        stack: '任务总数',
        data: $('#bar-data').data('todocount')
    }]
};
barChart.setOption(barChartOption);

/***/ })

/******/ });