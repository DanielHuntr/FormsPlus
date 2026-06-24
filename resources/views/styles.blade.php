@extends('statamic::layout')
@section('title', 'Form Theme')

@section('content')
    <forms-plus-styles
        styles-url="{{ $stylesApiUrl }}"
        styles-save-url="{{ $stylesApiUrl }}"
    ></forms-plus-styles>
@endsection
