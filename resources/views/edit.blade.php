@extends('statamic::layout')
@section('title', $form['title'].' — Form Builder')

@section('content')
    <forms-plus-builder
        form-json="{{ json_encode($form) }}"
        fields-url="{{ $fieldsUrl }}"
        save-url="{{ $saveUrl }}"
        index-url="{{ $indexUrl }}"
        submissions-url="{{ $submissionsUrl }}"
        export-url="{{ $exportUrl }}"
        settings-url="{{ $settingsUrl }}"
        settings-save-url="{{ $settingsSaveUrl }}"
        email-notification-url="{{ $emailNotificationUrl }}"
        email-confirmation-url="{{ $emailConfirmationUrl }}"
        email-preview-url="{{ cp_route('forms-plus.email-preview') }}"
        preview-url="{{ $previewUrl }}"
    ></forms-plus-builder>
@endsection
