@extends('layouts.default')
@section('title', '得意先一覧')


@section('content')

<newcustomerlist-component 
        :is-editable="{{ $isEditable }}" 
        :customerlist="{{ $customerList }}"
        :matterlist="{{ $matterList }}"
        :departmentlist="{{ $departmentList }}"
        :staffdepartmentlist="{{ $staffDepartmentList }}"
        :stafflist="{{ $staffList }}"
></newcustomerlist-component>

@endsection