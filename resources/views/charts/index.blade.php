@extends('layouts.app')
@section('css')
    <!-- 通过cdn方式引入ECharts -->
    <script src="https://cdn.bootcss.com/echarts/3.6.1/echarts.js"></script>
@endsection
@section('content')
    <div class="container">
        <div class="col-md-4">
            <!-- 为 ECharts 饼状图 准备一个具备大小（宽高）的 DOM -->
            <div id="pieChart" style="width: 100%;height: 300px"></div>
            <div id="pie-data" data-total={{ $taskTotal }} data-todo={{ $todoCount }} data-done={{ $doneCount }}></div>
        </div>
        <div class="col-md-4">
            <!-- 为 ECharts 柱状图 准备一个具备大小（宽高）的 DOM -->
            <div id="barChart" style="width: 100%;height: 300px"></div>
            <div id="bar-data"
                 data-projecttotal={{ $projectTotal }}
                 data-projectnamelist={!! $projectNameList !!}
                 data-totalcount={!! json_encode(TaskCountArray($projects)) !!}
                 data-donecount={!! json_encode(DoneTaskCountArray($projects)) !!}
                 data-todocount={!! json_encode(TodoTaskCountArray($projects)) !!}
            ></div>
        </div>
        <div class="col-md-4">
            <!-- 为 ECharts 雷达状图 准备一个具备大小（宽高）的 DOM -->
            <div id="radarChart" style="width: 100%;height: 300px"></div>
            <div id="radar-data"
                 data-projectnamelist={!! $projectNameList !!}
                 data-max={{ getMax(TaskCountArray($projects)) }}
                 data-data={!! json_encode(data($projects)) !!}
            ></div>
        </div>
    </div>
@endsection
@section('js')
    <script src="{{ asset('js/charts.js') }}"></script>
    <script>

    </script>
@endsection