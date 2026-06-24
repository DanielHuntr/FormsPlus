<template>
    <div class="ff-editor">
        <div class="ff-editor__header">
            <h3 class="ff-editor__title">{{ fieldTypeLabel }}</h3>
            <button class="ff-editor__close" @click="$emit('close')" aria-label="Close panel">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
            </button>
        </div>

        <div class="ff-editor__body">
            <!-- Label -->
            <div class="ff-field">
                <label class="ff-label">Label</label>
                <input class="ff-input" type="text" :value="local.display" @input="set('display', $event.target.value)">
            </div>

            <!-- Handle -->
            <div class="ff-field">
                <label class="ff-label">Handle</label>
                <input class="ff-input ff-input--mono" type="text" :value="local.handle" @input="set('handle', $event.target.value)">
                <p class="ff-hint">Used as the field name in submissions.</p>
            </div>

            <!-- Instructions -->
            <div class="ff-field">
                <label class="ff-label">Instructions</label>
                <input class="ff-input" type="text" :value="local.instructions" @input="set('instructions', $event.target.value)" placeholder="Help text shown below the label">
            </div>

            <!-- Placeholder (text, textarea, select) -->
            <div v-if="hasPlaceholder" class="ff-field">
                <label class="ff-label">Placeholder</label>
                <input class="ff-input" type="text" :value="local.placeholder" @input="set('placeholder', $event.target.value)">
            </div>

            <!-- Width -->
            <div class="ff-field">
                <label class="ff-label">Width</label>
                <div class="ff-width-grid">
                    <button
                        v-for="w in [25, 33, 50, 75, 100]"
                        :key="w"
                        class="ff-width-btn"
                        :class="{ 'ff-width-btn--active': local.width === w }"
                        @click="set('width', w)"
                    >{{ w }}%</button>
                </div>
            </div>

            <!-- Required -->
            <div class="ff-field ff-field--row">
                <label class="ff-label ff-label--inline">Required</label>
                <button
                    class="ff-toggle"
                    :class="{ 'ff-toggle--on': local.required }"
                    @click="set('required', !local.required)"
                    :aria-pressed="local.required"
                    role="switch"
                >
                    <span class="ff-toggle__thumb"></span>
                </button>
            </div>

            <!-- Rows (textarea only) -->
            <div v-if="local.type === 'textarea'" class="ff-field">
                <label class="ff-label">Rows</label>
                <input class="ff-input" type="number" min="2" max="20" :value="local.rows" @input="set('rows', parseInt($event.target.value) || 3)">
            </div>

            <!-- Character limit (text, textarea) -->
            <div v-if="['text', 'textarea'].includes(local.type)" class="ff-field">
                <label class="ff-label">Character limit <span class="ff-hint-inline">(optional)</span></label>
                <input class="ff-input" type="number" min="1" :value="local.character_limit || ''" @input="set('character_limit', $event.target.value ? parseInt($event.target.value) : null)" placeholder="No limit">
            </div>

            <!-- Multiple (select, files) -->
            <div v-if="['select', 'files'].includes(local.type)" class="ff-field ff-field--row">
                <label class="ff-label ff-label--inline">Allow multiple</label>
                <button
                    class="ff-toggle"
                    :class="{ 'ff-toggle--on': local.multiple }"
                    @click="set('multiple', !local.multiple)"
                    :aria-pressed="local.multiple"
                    role="switch"
                >
                    <span class="ff-toggle__thumb"></span>
                </button>
            </div>

            <!-- Allowed extensions (files) -->
            <div v-if="local.type === 'files'" class="ff-field">
                <label class="ff-label">Allowed extensions <span class="ff-hint-inline">(optional)</span></label>
                <input
                    class="ff-input"
                    type="text"
                    :value="(local.allowed_extensions || []).join(', ')"
                    @input="set('allowed_extensions', $event.target.value.split(',').map(s => s.trim()).filter(Boolean))"
                    placeholder="pdf, doc, jpg"
                >
                <p class="ff-hint">Comma-separated. Leave blank to allow any type.</p>
            </div>

            <!-- Options (select, radio, checkboxes) -->
            <div v-if="hasOptions" class="ff-field">
                <label class="ff-label">Options</label>
                <div class="ff-options">
                    <div
                        v-for="(opt, i) in local.options"
                        :key="i"
                        class="ff-option"
                    >
                        <input
                            class="ff-input ff-option__label"
                            type="text"
                            :value="opt.label"
                            @input="updateOption(i, 'label', $event.target.value)"
                            placeholder="Label"
                        >
                        <input
                            class="ff-input ff-input--mono ff-option__value"
                            type="text"
                            :value="opt.value"
                            @input="updateOption(i, 'value', $event.target.value)"
                            placeholder="value"
                        >
                        <button class="ff-option__remove" @click="removeOption(i)" title="Remove option" :disabled="local.options.length <= 1">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                        </button>
                    </div>
                </div>
                <button class="ff-btn ff-btn--ghost ff-btn--sm ff-btn--add-option" @click="addOption">
                    + Add option
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    emits: ['update', 'close'],

    props: {
        field: { type: Object, required: true },
    },

    data() {
        return {
            local: { ...this.field, options: JSON.parse(JSON.stringify(this.field.options || [])) },
        };
    },

    watch: {
        field: {
            deep: true,
            handler(newVal) {
                this.local = { ...newVal, options: JSON.parse(JSON.stringify(newVal.options || [])) };
            },
        },
    },

    computed: {
        fieldTypeLabel() {
            if (this.local.type === 'text') {
                const map = { email: 'Email', tel: 'Phone', number: 'Number', text: 'Text' };
                return (map[this.local.input_type] ?? 'Text') + ' Field';
            }
            const map = {
                textarea: 'Textarea',
                select: 'Select',
                radio: 'Radio Group',
                checkboxes: 'Checkboxes',
                files: 'File Upload',
            };
            return (map[this.local.type] ?? this.local.type) + ' Field';
        },

        hasPlaceholder() {
            return ['text', 'textarea', 'select'].includes(this.local.type);
        },

        hasOptions() {
            return ['select', 'radio', 'checkboxes'].includes(this.local.type);
        },
    },

    methods: {
        set(key, value) {
            this.local = { ...this.local, [key]: value };
            this.$emit('update', { [key]: value });
        },

        updateOption(index, key, value) {
            const options = JSON.parse(JSON.stringify(this.local.options));
            options[index][key] = value;

            // Auto-sync value from label if value hasn't been manually edited
            if (key === 'label') {
                const autoValue = value.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
                options[index].value = autoValue;
            }

            this.local = { ...this.local, options };
            this.$emit('update', { options });
        },

        addOption() {
            const i = this.local.options.length + 1;
            const options = [...(this.local.options || []), { label: `Option ${i}`, value: `option_${i}` }];
            this.local = { ...this.local, options };
            this.$emit('update', { options });
        },

        removeOption(index) {
            if (this.local.options.length <= 1) return;
            const options = this.local.options.filter((_, i) => i !== index);
            this.local = { ...this.local, options };
            this.$emit('update', { options });
        },
    },
};
</script>
