@extends('layouts.app')
@section('css')
    <link href="https://cdn.bootcss.com/font-awesome/4.7.0/css/font-awesome.css" rel="stylesheet">
@endsection
@section('content')
    <div class="container">
        <h1>Task:{{ $task->name }}</h1>
        <steps></steps>
    </div>
@endsection