@extends('layouts.default')
@section('title', '請求一覧')

@section('content')

<requestlist-component 
    :customerlist="{{ $customerList }}"
    :stafflist="{{ $staffList }}"
    :departmentlist="{{ $departmentList }}"
    :initsearchparams="{{ $initSearchParams }}"
    :authclosing="{{ $authClosing }}"
    :authinvoice="{{ $authInvoice }}"
></requestlist-component>

@endsection