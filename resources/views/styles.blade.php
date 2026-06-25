@extends('statamic::layout')
@section('title', 'Form Theme')

@section('content')
    <forms-plus-styles
        styles-url="{{ $stylesApiUrl }}"
        styles-save-url="{{ $stylesApiUrl }}"
        css-files-url="{{ cp_route('forms-plus.styles.css-files') }}"
        css-content-url="{{ cp_route('forms-plus.styles.css-content') }}"
        build-css-url="{{ cp_route('forms-plus.styles.build-css') }}"
    ></forms-plus-styles>
@endsection
