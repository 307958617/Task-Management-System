<?php

function TaskCountArray($projects)
{
    $projectTaskCountArray = [];
    foreach($projects as $project){
        $projectTaskCount = $project->tasks()->count();
        array_push($projectTaskCountArray,$projectTaskCount);
    }
    return $projectTaskCountArray;
}

function TodoTaskCountArray($projects)
{
    $projectTaskCountArray = [];
    foreach($projects as $project){
        $projectTaskCount = $project->tasks()->where('completed','F')->count();
        array_push($projectTaskCountArray,$projectTaskCount);
    }
    return $projectTaskCountArray;
}

function DoneTaskCountArray($projects)
{
    $projectTaskCountArray = [];
    foreach($projects as $project){
        $projectTaskCount = $project->tasks()->where('completed','T')->count();
        array_push($projectTaskCountArray,$projectTaskCount);
    }
    return $projectTaskCountArray;
}

function getMax($arr)
{
    $max=$arr[0];
    foreach($arr as $k=>$v){
      if($v>$max){
          $max=$v;
      }
    }
    return $max;
}

function data($projects)
{
    $data = [];
    foreach ($projects as $project){
        $name = $project->name;
        $totalPP = $project->tasks()->count();
        $todoPP = $project->tasks()->where('completed','F')->count();
        $donePP = $project->tasks()->where('completed','T')->count();
        $date = '{"value":['.$totalPP.','.$todoPP.','.$donePP.'],"name":'.'"'.$name.'"}';
        array_push($data,json_decode($date,true));
    }
    return $data;
}