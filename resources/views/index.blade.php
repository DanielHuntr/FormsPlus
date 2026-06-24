@extends('statamic::layout')
@section('title', 'Forms Plus')

@section('content')
    <forms-plus-index
        initial-forms="{{ json_encode($forms) }}"
        store-url="{{ cp_route('forms-plus.store') }}"
    ></forms-plus-index>
@endsection
