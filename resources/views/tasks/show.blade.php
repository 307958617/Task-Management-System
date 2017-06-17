@extends('layouts.app')
@section('css')
    <link href="https://cdn.bootcss.com/animate.css/3.5.2/animate.css" rel="stylesheet">
@endsection
@section('content')
    <div class="container">
        <h1>Task:{{ $task->name }}</h1>
        <steps></steps>
    </div>
@endsection