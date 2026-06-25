@php
    $settings    = $settings ?? [];
    $submitted   = $submitted ?? false;
    $enabled     = $settings['enabled'] ?? true;
    $submitLabel = $submitLabel ?? $settings['submit_label'] ?? 'Submit';
    $hasFile     = $fields->contains('type', 'files');
@endphp

{!! \App\FormsPlus\StylesManager::renderStyles() !!}

{{-- Success state --}}
@if ($submitted)
    <div class="flexible-form flexible-form--success">
        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" class="flexible-form__success-icon"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
        <h3 class="flexible-form__success-title">{{ $settings['success_title'] ?? 'Message sent!' }}</h3>
        <p class="flexible-form__success-message">{{ $settings['success_message'] ?? 'Thank you for your submission.' }}</p>
    </div>

{{-- Disabled state --}}
@elseif (! $enabled)
    <div class="flexible-form flexible-form--disabled">
        <p>This form is currently unavailable.</p>
    </div>

{{-- Normal form --}}
@else
    <form
        action="{{ $actionUrl }}"
        method="POST"
        class="flexible-form"
        @if ($hasFile) enctype="multipart/form-data" @endif
    >
        @csrf

        @if (! empty($redirect))
            <input type="hidden" name="_redirect" value="{{ $redirect }}">
        @endif
        @if (! empty($errorRedirect))
            <input type="hidden" name="_error_redirect" value="{{ $errorRedirect }}">
        @endif

        @if ($errors->any())
            <div class="flexible-form__errors" role="alert">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="flexible-form__fields">
            @foreach ($fields as $field)
                @php
                    $hasError     = $errors->has($field['handle']);
                    $wrapperClass = 'flexible-form__field' . ($hasError ? ' flexible-form__field--error' : '');
                    $inputClass   = 'flexible-form__input' . ($hasError ? ' flexible-form__input--error' : '');
                @endphp

                <div class="{{ $wrapperClass }}" style="--field-width: {{ $field['width'] ?? 100 }}%">
                    @if (! in_array($field['type'], ['checkboxes', 'radio']))
                        <label class="flexible-form__label" for="{{ $field['handle'] }}">
                            {!! $field['display'] !!}
                            @if ($field['required'])
                                <span class="flexible-form__required" aria-hidden="true">*</span>
                            @endif
                        </label>
                    @endif

                    @if (! empty($field['instructions']))
                        <p class="flexible-form__instructions">{{ $field['instructions'] }}</p>
                    @endif

                    @switch($field['type'])
                        @case('text')
                            <input
                                type="{{ $field['input_type'] ?? 'text' }}"
                                name="{{ $field['handle'] }}"
                                id="{{ $field['handle'] }}"
                                class="{{ $inputClass }}"
                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                value="{{ old($field['handle']) }}"
                                @if (! empty($field['character_limit'])) maxlength="{{ $field['character_limit'] }}" @endif
                                @if ($field['required']) required @endif
                            >
                            @break

                        @case('textarea')
                            <textarea
                                name="{{ $field['handle'] }}"
                                id="{{ $field['handle'] }}"
                                class="flexible-form__input flexible-form__textarea{{ $hasError ? ' flexible-form__input--error' : '' }}"
                                placeholder="{{ $field['placeholder'] ?? '' }}"
                                rows="{{ $field['rows'] ?? 3 }}"
                                @if (! empty($field['character_limit'])) maxlength="{{ $field['character_limit'] }}" @endif
                                @if ($field['required']) required @endif
                            >{{ old($field['handle']) }}</textarea>
                            @break

                        @case('select')
                            <select
                                name="{{ $field['handle'] }}{{ ($field['multiple'] ?? false) ? '[]' : '' }}"
                                id="{{ $field['handle'] }}"
                                class="flexible-form__input flexible-form__select{{ $hasError ? ' flexible-form__input--error' : '' }}"
                                @if ($field['multiple'] ?? false) multiple @endif
                                @if ($field['required']) required @endif
                            >
                                <option value="">{{ $field['placeholder'] ?: 'Please select…' }}</option>
                                @foreach ($field['options'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old($field['handle']) === (string) $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @break

                        @case('checkboxes')
                            <fieldset class="flexible-form__fieldset">
                                <legend class="flexible-form__label">
                                    {!! $field['display'] !!}
                                    @if ($field['required'])
                                        <span class="flexible-form__required" aria-hidden="true">*</span>
                                    @endif
                                </legend>
                                @if (! empty($field['instructions']))
                                    <p class="flexible-form__instructions">{{ $field['instructions'] }}</p>
                                @endif
                                <div class="flexible-form__check-group">
                                    @foreach ($field['options'] as $value => $label)
                                        <label class="flexible-form__check-label">
                                            <input
                                                type="checkbox"
                                                name="{{ $field['handle'] }}[]"
                                                value="{{ $value }}"
                                                class="flexible-form__checkbox"
                                                @checked(in_array((string) $value, (array) old($field['handle'], [])))
                                            >
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                            @break

                        @case('radio')
                            <fieldset class="flexible-form__fieldset">
                                <legend class="flexible-form__label">
                                    {!! $field['display'] !!}
                                    @if ($field['required'])
                                        <span class="flexible-form__required" aria-hidden="true">*</span>
                                    @endif
                                </legend>
                                @if (! empty($field['instructions']))
                                    <p class="flexible-form__instructions">{{ $field['instructions'] }}</p>
                                @endif
                                <div class="flexible-form__check-group">
                                    @foreach ($field['options'] as $value => $label)
                                        <label class="flexible-form__check-label">
                                            <input
                                                type="radio"
                                                name="{{ $field['handle'] }}"
                                                value="{{ $value }}"
                                                class="flexible-form__radio"
                                                @checked(old($field['handle']) === (string) $value)
                                                @if ($field['required']) required @endif
                                            >
                                            {{ $label }}
                                        </label>
                                    @endforeach
                                </div>
                            </fieldset>
                            @break

                        @case('files')
                            <input
                                type="file"
                                name="{{ $field['handle'] }}{{ ($field['multiple'] ?? false) ? '[]' : '' }}"
                                id="{{ $field['handle'] }}"
                                class="{{ $inputClass }} flexible-form__file"
                                @if ($field['multiple'] ?? false) multiple @endif
                                @if ($field['required']) required @endif
                                @if (! empty($field['allowed_extensions']))
                                    accept=".{{ implode(',.', $field['allowed_extensions']) }}"
                                @endif
                            >
                            @break
                    @endswitch

                    @error($field['handle'])
                        <p class="flexible-form__error-msg" role="alert">{{ $message }}</p>
                    @enderror
                </div>
            @endforeach
        </div>

        <div style="position:absolute;left:-9999px;top:auto;width:1px;height:1px;overflow:hidden;" aria-hidden="true">
            <label for="fp_website">Website</label>
            <input type="text" name="fp_website" id="fp_website" tabindex="-1" autocomplete="off" value="">
        </div>

        <div class="flexible-form__submit">
            <button type="submit" class="flexible-form__button">
                {{ $submitLabel }}
            </button>
        </div>
    </form>
@endif
