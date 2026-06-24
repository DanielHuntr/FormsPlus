@extends('statamic::layout')
@section('title', 'Default Email Templates')

@section('content')
    <forms-plus-default-templates
        email-notification-url="{{ $emailNotificationUrl }}"
        email-confirmation-url="{{ $emailConfirmationUrl }}"
        email-preview-url="{{ cp_route('forms-plus.email-preview') }}"
    ></forms-plus-default-templates>
@endsection
