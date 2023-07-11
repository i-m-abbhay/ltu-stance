@extends('layouts.default')
@section('title', '得意先担当者設定')


@section('content')

<customerstaff-component 
        :customerlist="{{ $customerList }}"
        :stafflist="{{ $staffList }}"
        :departmentlist="{{ $departmentList }}"
        :staffdeartlist="{{ $staffDepartList }}"
></customerstaff-component>

@endsection