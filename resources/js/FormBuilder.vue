<template>
    <div class="ff-wrap">
        <!-- Header -->
        <header class="ff-header">
            <div class="ff-header__left">
                <a :href="indexUrl" class="ff-back">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
                    Forms
                </a>
                <h1 class="ff-title">{{ form.title }}</h1>
            </div>
            <div class="ff-header__right">
                <template v-if="currentTab === 'fields'">
                    <span v-if="saveStatus" class="ff-save-status" :class="saveStatus === 'saved' ? 'ff-save-status--ok' : 'ff-save-status--saving'">
                        {{ saveStatus === 'saved' ? 'Saved' : 'Saving…' }}
                    </span>
                    <button class="ff-btn ff-btn--primary" @click="save" :disabled="saving">
                        {{ saving ? 'Saving…' : 'Save Form' }}
                    </button>
                </template>
                <template v-else-if="currentTab === 'submissions'">
                    <a :href="exportUrl" class="ff-btn ff-btn--primary" download>
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:6px"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 13 7 8"></polyline><line x1="12" y1="2" x2="12" y2="13"></line></svg>
                        Export CSV
                    </a>
                </template>
            </div>
        </header>

        <!-- Tab navigation -->
        <nav class="ff-tabs">
            <button
                v-for="tab in tabs"
                :key="tab.key"
                class="ff-tab"
                :class="{ 'ff-tab--active': currentTab === tab.key }"
                @click="currentTab = tab.key"
            >
                {{ tab.label }}
            </button>
        </nav>

        <!-- Fields tab -->
        <div v-show="currentTab === 'fields'" class="ff-builder">
            <!-- Field type palette -->
            <aside class="ff-palette">
                <h3 class="ff-palette__heading">Add Field</h3>
                <div class="ff-palette__list">
                    <button
                        v-for="type in fieldTypes"
                        :key="type.key"
                        class="ff-palette__item"
                        @click="addField(type)"
                    >
                        <span class="ff-palette__icon" v-html="type.icon"></span>
                        <span class="ff-palette__label">{{ type.label }}</span>
                    </button>
                </div>

                <!-- Snippet hint -->
                <div class="ff-palette__hint" v-if="fields.length > 0">
                    <p class="ff-palette__hint-heading">Antlers tag</p>
                    <code class="ff-palette__code">{{ antlersTag }}</code>
                </div>
            </aside>

            <!-- Canvas -->
            <main class="ff-canvas">
                <div v-if="fields.length === 0" class="ff-canvas__empty">
                    <p>Click a field type on the left to add it to your form.</p>
                </div>

                <div
                    v-for="(field, index) in fields"
                    :key="field._uid"
                    class="ff-field-row"
                    :class="{
                        'ff-field-row--active': activeIndex === index,
                        'ff-field-row--dragging': dragIndex === index,
                        'ff-field-row--dragover': dragOverIndex === index && dragOverIndex !== dragIndex,
                    }"
                    :style="{ width: field.width + '%' }"
                    draggable="true"
                    @dragstart="onDragStart(index)"
                    @dragover.prevent="onDragOver(index)"
                    @dragend="onDragEnd"
                    @click="setActive(index)"
                >
                    <div class="ff-field-row__handle" title="Drag to reorder">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1"/><circle cx="9" cy="12" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="19" r="1"/></svg>
                    </div>
                    <div class="ff-field-row__preview">
                        <span class="ff-field-row__type-badge">{{ fieldTypeLabel(field.type, field.input_type) }}</span>
                        <span class="ff-field-row__label">{{ field.display || 'Untitled field' }}</span>
                        <span v-if="field.required" class="ff-field-row__required">Required</span>
                    </div>
                    <div class="ff-field-row__actions">
                        <button class="ff-field-row__action" @click.stop="duplicateField(index)" title="Duplicate">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                        </button>
                        <button class="ff-field-row__action ff-field-row__action--danger" @click.stop="removeField(index)" title="Remove">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>
                        </button>
                    </div>
                </div>
            </main>

            <!-- Field editor panel -->
            <aside class="ff-editor-panel" v-if="activeField">
                <FieldEditor
                    :field="activeField"
                    @update="updateActiveField"
                    @close="activeIndex = null"
                />
            </aside>
        </div>

        <!-- Submissions tab -->
        <SubmissionsPanel
            v-if="currentTab === 'submissions'"
            :submissions-url="submissionsUrl"
            :export-url="exportUrl"
        />

        <!-- Settings tab -->
        <SettingsPanel
            v-if="currentTab === 'settings'"
            :settings-url="settingsUrl"
            :settings-save-url="settingsSaveUrl"
            :fields="fields"
        />

        <!-- Email tab -->
        <FormEmailTab
            v-if="currentTab === 'email'"
            :email-notification-url="emailNotificationUrl"
            :email-confirmation-url="emailConfirmationUrl"
        />

    </div>
</template>

<script>
import FieldEditor       from './FieldEditor.vue';
import SubmissionsPanel  from './SubmissionsPanel.vue';
import SettingsPanel     from './SettingsPanel.vue';
import FormEmailTab      from './FormEmailTab.vue';

let uid = 0;

const FIELD_TYPES = [
    {
        key: 'text',
        label: 'Text',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 7 4 4 20 4 20 7"></polyline><line x1="9" y1="20" x2="15" y2="20"></line><line x1="12" y1="4" x2="12" y2="20"></line></svg>',
        defaults: { type: 'text', input_type: 'text', display: 'Text', handle: '' },
    },
    {
        key: 'email',
        label: 'Email',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>',
        defaults: { type: 'text', input_type: 'email', display: 'Email Address', handle: '' },
    },
    {
        key: 'phone',
        label: 'Phone',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 9.81a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 3h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 10.6a16 16 0 0 0 6 6l.93-.93a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"></path></svg>',
        defaults: { type: 'text', input_type: 'tel', display: 'Phone Number', handle: '' },
    },
    {
        key: 'number',
        label: 'Number',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" y1="9" x2="20" y2="9"></line><line x1="4" y1="15" x2="20" y2="15"></line><line x1="10" y1="3" x2="8" y2="21"></line><line x1="16" y1="3" x2="14" y2="21"></line></svg>',
        defaults: { type: 'text', input_type: 'number', display: 'Number', handle: '' },
    },
    {
        key: 'textarea',
        label: 'Textarea',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="21" y1="10" x2="3" y2="10"></line><line x1="21" y1="6" x2="3" y2="6"></line><line x1="21" y1="14" x2="3" y2="14"></line><line x1="21" y1="18" x2="11" y2="18"></line></svg>',
        defaults: { type: 'textarea', display: 'Message', handle: '', rows: 4 },
    },
    {
        key: 'select',
        label: 'Select',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 12 15 18 9"></polyline></svg>',
        defaults: { type: 'select', display: 'Select', handle: '', options: [{ value: 'option_1', label: 'Option 1' }], multiple: false },
    },
    {
        key: 'radio',
        label: 'Radio',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><circle cx="12" cy="12" r="3"></circle></svg>',
        defaults: { type: 'radio', display: 'Radio Group', handle: '', options: [{ value: 'option_1', label: 'Option 1' }] },
    },
    {
        key: 'checkboxes',
        label: 'Checkboxes',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>',
        defaults: { type: 'checkboxes', display: 'Checkboxes', handle: '', options: [{ value: 'option_1', label: 'Option 1' }] },
    },
    {
        key: 'files',
        label: 'File Upload',
        icon: '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 3 7 8"></polyline><line x1="12" y1="3" x2="12" y2="15"></line></svg>',
        defaults: { type: 'files', display: 'File Upload', handle: '', multiple: false, allowed_extensions: [] },
    },
];

export default {
    components: { FieldEditor, SubmissionsPanel, SettingsPanel, FormEmailTab },

    props: {
        formJson:        { type: String, default: '{}' },
        fieldsUrl:       { type: String, required: true },
        saveUrl:         { type: String, required: true },
        indexUrl:        { type: String, required: true },
        submissionsUrl:  { type: String, required: true },
        exportUrl:       { type: String, required: true },
        settingsUrl:          { type: String, required: true },
        settingsSaveUrl:      { type: String, required: true },
        emailNotificationUrl: { type: String, required: true },
        emailConfirmationUrl: { type: String, required: true },
    },

    data() {
        const form = JSON.parse(this.formJson || '{}');
        return {
            form,
            fields:        [],
            activeIndex:   null,
            saving:        false,
            saveStatus:    null,
            loading:       true,
            fieldTypes:    FIELD_TYPES,
            dragIndex:     null,
            dragOverIndex: null,
            currentTab:    'fields',
            tabs: [
                { key: 'fields',      label: 'Fields' },
                { key: 'submissions', label: 'Submissions' },
                { key: 'settings',    label: 'Settings' },
                { key: 'email',       label: 'Email' },
            ],
        };
    },

    computed: {
        activeField() {
            return this.activeIndex !== null ? this.fields[this.activeIndex] : null;
        },

        antlersTag() {
            return `{{ forms_plus handle="${this.form.handle}" }}`;
        },
    },

    async mounted() {
        await this.loadFields();
    },

    methods: {
        async loadFields() {
            this.loading = true;
            try {
                const { data } = await window.axios.get(this.fieldsUrl);
                this.fields = data.map(f => ({ ...f, _uid: ++uid }));
            } catch {
                console.error('Failed to load fields.');
            } finally {
                this.loading = false;
            }
        },

        addField(type) {
            const base = {
                _uid: ++uid,
                handle: this.uniqueHandle(type.defaults.display),
                display: type.defaults.display,
                placeholder: '',
                instructions: '',
                required: false,
                width: 100,
                character_limit: null,
                allowed_extensions: [],
                options: [],
                multiple: false,
                rows: 4,
                input_type: 'text',
                ...type.defaults,
            };
            this.fields.push(base);
            this.activeIndex = this.fields.length - 1;
        },

        uniqueHandle(display) {
            const base = display.toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
            let handle = base;
            let i = 1;
            while (this.fields.some(f => f.handle === handle)) {
                handle = `${base}_${i++}`;
            }
            return handle;
        },

        removeField(index) {
            if (this.activeIndex === index) this.activeIndex = null;
            else if (this.activeIndex > index) this.activeIndex--;
            this.fields.splice(index, 1);
        },

        duplicateField(index) {
            const copy = { ...this.fields[index], _uid: ++uid };
            copy.handle = this.uniqueHandle(copy.display);
            this.fields.splice(index + 1, 0, copy);
            this.activeIndex = index + 1;
        },

        setActive(index) {
            this.activeIndex = this.activeIndex === index ? null : index;
        },

        updateActiveField(updates) {
            if (this.activeIndex === null) return;
            this.fields[this.activeIndex] = { ...this.fields[this.activeIndex], ...updates };
        },

        fieldTypeLabel(type, inputType) {
            if (type === 'text') {
                const map = { email: 'Email', tel: 'Phone', number: 'Number', text: 'Text' };
                return map[inputType] ?? 'Text';
            }
            const map = { textarea: 'Textarea', select: 'Select', radio: 'Radio', checkboxes: 'Checkboxes', files: 'File Upload' };
            return map[type] ?? type;
        },

        async save() {
            this.saving     = true;
            this.saveStatus = 'saving';
            try {
                const { data } = await window.axios.post(this.saveUrl, { fields: this.fields });
                if (data.success) {
                    this.saveStatus = 'saved';
                    setTimeout(() => { this.saveStatus = null; }, 3000);
                }
            } catch {
                alert('Save failed. Please try again.');
                this.saveStatus = null;
            } finally {
                this.saving = false;
            }
        },

        onDragStart(index) { this.dragIndex = index; },
        onDragOver(index)  { this.dragOverIndex = index; },

        onDragEnd() {
            if (this.dragIndex !== null && this.dragOverIndex !== null && this.dragIndex !== this.dragOverIndex) {
                const moved = this.fields.splice(this.dragIndex, 1)[0];
                this.fields.splice(this.dragOverIndex, 0, moved);
                if (this.activeIndex === this.dragIndex) {
                    this.activeIndex = this.dragOverIndex;
                }
            }
            this.dragIndex = null;
            this.dragOverIndex = null;
        },
    },
};
</script>
