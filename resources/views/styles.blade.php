@extends('statamic::layout')
@section('title', 'Form Theme')

@section('content')
    <forms-plus-styles
        styles-url="{{ $stylesApiUrl }}"
        styles-save-url="{{ $stylesApiUrl }}"
        css-files-url="{{ cp_route('forms-plus.styles.css-files') }}"
    ></forms-plus-styles>
@endsection
