@extends('statamic::layout')
@section('title', 'Mail Settings — Forms Plus')

@section('content')
    <forms-plus-mail-settings
        :api-url="'{{ cp_route('forms-plus.mail-settings.api') }}'"
        :save-url="'{{ cp_route('forms-plus.mail-settings.save') }}'"
        :test-url="'{{ cp_route('forms-plus.mail-settings.test') }}'"
    ></forms-plus-mail-settings>
@endsection
