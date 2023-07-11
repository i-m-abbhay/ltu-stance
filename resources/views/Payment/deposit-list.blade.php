@extends('layouts.default')
@section('title', '入金一覧')

@section('content')

<depositlist-component 
        :customerlist="{{ $customerList }}"
        :banklist="{{ $bankList }}"
        :branchlist="{{ $branchList }}"
        :stafflist="{{ $staffList }}"
        :departmentlist="{{ $departmentList }}"
        :initsearchparams="{{ $initSearchParams }}"
        :authclosing="{{ $authClosing }}"
        :authinvoice="{{ $authInvoice }}"
></depositlist-component>

@endsection
