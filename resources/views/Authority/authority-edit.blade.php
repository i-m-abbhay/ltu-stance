@extends('layouts.default')
@section('title', '権限管理')

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>権限管理</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

@section('content')

<authorityedit-component 
:authority-binding-prefix="{{ $authorityBindingPrefix }}"
:auth-data="{{ $departmentData }}" 
:department-data="{{ $departmentData }}" 
:staff-data="{{ $staffData }}" 
:staff-department-data="{{ $staffDepartmentData }}" 
:authority-data="{{ $authorityData }}" 
></authorityedit-component>

@endsection