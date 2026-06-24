@extends('statamic::layout')
@section('title', 'Default Email Templates')

@section('content')
    <forms-plus-default-templates
        email-notification-url="{{ $emailNotificationUrl }}"
        email-confirmation-url="{{ $emailConfirmationUrl }}"
    ></forms-plus-default-templates>
@endsection
