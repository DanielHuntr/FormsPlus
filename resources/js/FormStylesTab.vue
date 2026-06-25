<template>
    <div class="fst">
        <div v-if="loading" class="fst__loading">Loading styles…</div>

        <div v-else class="fst__layout">
            <!-- LEFT: Element class editors -->
            <aside class="fst__sidebar">
                <div class="fst__sidebar-inner">

                    <!-- Preset picker -->
                    <div class="fst__group">
                        <div class="fst__group-title">Preset themes</div>
                        <div class="fst__presets">
                            <button
                                v-for="preset in presets"
                                :key="preset.id"
                                class="fst__preset-btn"
                                @click="applyPreset(preset)"
                                :title="preset.label"
                            >{{ preset.label }}</button>
                        </div>
                    </div>

                    <!-- Form structure -->
                    <div class="fst__group">
                        <div class="fst__group-title">Layout</div>
                        <div class="fst__field" v-for="el in layoutElements" :key="el.key">
                            <label class="fst__label">{{ el.label }}</label>
                            <input v-model="styles[el.key]" type="text" class="fst__input" :placeholder="el.placeholder" @input="debouncedRefresh">
                        </div>
                    </div>

                    <!-- Text & Inputs -->
                    <div class="fst__group">
                        <div class="fst__group-title">Text &amp; Inputs</div>
                        <div class="fst__field" v-for="el in inputElements" :key="el.key">
                            <label class="fst__label">{{ el.label }}</label>
                            <input v-model="styles[el.key]" type="text" class="fst__input" :placeholder="el.placeholder" @input="debouncedRefresh">
                        </div>
                    </div>

                    <!-- Choices -->
                    <div class="fst__group">
                        <div class="fst__group-title">Checkboxes &amp; Radios</div>
                        <div class="fst__field" v-for="el in choiceElements" :key="el.key">
                            <label class="fst__label">{{ el.label }}</label>
                            <input v-model="styles[el.key]" type="text" class="fst__input" :placeholder="el.placeholder" @input="debouncedRefresh">
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="fst__group">
                        <div class="fst__group-title">Actions &amp; Feedback</div>
                        <div class="fst__field" v-for="el in feedbackElements" :key="el.key">
                            <label class="fst__label">{{ el.label }}</label>
                            <input v-model="styles[el.key]" type="text" class="fst__input" :placeholder="el.placeholder" @input="debouncedRefresh">
                        </div>
                    </div>

                    <!-- Preview stylesheet -->
                    <div class="fst__group">
                        <div class="fst__group-title">Preview stylesheet</div>
                        <p class="fst__hint">URL to your site's CSS file so custom properties (e.g. <code>var(--color-brand)</code>) resolve in the live preview. Not injected into the form itself.</p>
                        <input v-model="styles.preview_stylesheet" type="url" class="fst__input" placeholder="e.g. /css/site.css" @input="debouncedRefresh">
                    </div>

                    <!-- Custom CSS -->
                    <div class="fst__group">
                        <div class="fst__group-title-row">
                            <span class="fst__group-title fst__group-title--bare">Custom CSS</span>
                            <button class="fst__css-dock-btn" @click="toggleDock" :title="docked ? 'Undock editor' : 'Dock editor'">
                                <svg v-if="docked" xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                                <svg v-else xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="10" y1="14" x2="3" y2="21"/><line x1="21" y1="3" x2="14" y2="10"/></svg>
                            </button>
                        </div>
                        <p class="fst__hint">Injected as a <code>&lt;style&gt;</code> block on the page. Click a class below to insert a rule at your cursor.</p>

                        <div v-show="docked" ref="cssEditorMount" class="fst__css-editor"></div>
                        <div v-if="!docked" class="fst__css-editor-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                            Editor undocked — editing in panel below
                        </div>

                        <div class="fst__class-ref">
                            <p class="fst__class-ref-hint">Available classes — hover for details, click to insert:</p>
                            <div class="fst__class-chips">
                                <button
                                    v-for="cls in availableClasses"
                                    :key="cls.name"
                                    class="fst__class-chip"
                                    :title="cls.description"
                                    @click="insertClass(cls.name)"
                                >.{{ cls.name }}</button>
                            </div>
                        </div>
                    </div>

                    <!-- Save -->
                    <div class="fst__save-row">
                        <button class="ff-btn ff-btn--primary" :disabled="saving" @click="save">
                            {{ saving ? 'Saving…' : 'Save Styles' }}
                        </button>
                    </div>
                </div>
            </aside>

            <!-- RIGHT: Live preview -->
            <div class="fst__preview-pane">
                <div class="fst__preview-header">
                    <span class="fst__preview-title">Live Preview</span>
                    <div class="fst__mode-toggle">
                        <button
                            class="fst__mode-btn"
                            :class="{ 'fst__mode-btn--active': previewMode === 'light' }"
                            @click="previewMode = 'light'; refreshPreview()"
                        >
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                            Light
                        </button>
                        <button
                            class="fst__mode-btn"
                            :class="{ 'fst__mode-btn--active': previewMode === 'dark' }"
                            @click="previewMode = 'dark'; refreshPreview()"
                        >
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                            Dark
                        </button>
                    </div>
                    <p class="fst__preview-note">Preview uses Tailwind CDN. Your site's existing form CSS also applies in production.</p>
                </div>
                <iframe
                    class="fst__preview-frame"
                    :srcdoc="previewHtml"
                    title="Form preview"
                    sandbox="allow-same-origin allow-scripts"
                ></iframe>
            </div>
        </div>
        <!-- Undocked float panel -->
        <div v-if="!loading && !docked" class="fst__css-float">
            <div class="fst__css-float-header">
                <div class="fst__css-float-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="16 18 22 12 16 6"/><polyline points="8 6 2 12 8 18"/></svg>
                    Custom CSS
                </div>
                <button class="fst__css-float-dock-btn" @click="toggleDock">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="4 14 10 14 10 20"/><polyline points="20 10 14 10 14 4"/><line x1="10" y1="14" x2="3" y2="21"/><line x1="21" y1="3" x2="14" y2="10"/></svg>
                    Dock
                </button>
            </div>
            <div ref="cssEditorFloatMount" class="fst__css-float-body"></div>
        </div>
    </div>
</template>

<script>
import { EditorView, keymap } from '@codemirror/view';
import { EditorState } from '@codemirror/state';
import { basicSetup } from 'codemirror';
import { css as cssLang } from '@codemirror/lang-css';
import { oneDark } from '@codemirror/theme-one-dark';

const PRESETS = [
    {
        id: 'default',
        label: 'Default',
        styles: {
            form: '',
            wrapper: 'mb-5',
            label: 'block text-sm font-medium text-gray-700 mb-1 dark:text-gray-300',
            input: 'w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500',
            textarea: 'w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white',
            select: 'w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white',
            checkbox: 'h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600',
            radio: 'h-4 w-4 border-gray-300 text-blue-600 focus:ring-blue-500 dark:border-gray-600',
            choice_label: 'flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300',
            button: 'inline-flex items-center rounded-md bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors dark:bg-blue-500 dark:hover:bg-blue-400',
            error: 'mt-1 text-sm text-red-600 dark:text-red-400',
            hint: 'mt-1 text-xs text-gray-500 dark:text-gray-400',
            custom_css: '',
        },
    },
    {
        id: 'minimal',
        label: 'Minimal',
        styles: {
            form: '',
            wrapper: 'mb-6',
            label: 'block text-xs font-semibold uppercase tracking-widest text-gray-500 mb-2 dark:text-gray-400',
            input: 'w-full border-0 border-b-2 border-gray-200 bg-transparent px-0 py-2 text-sm text-gray-900 placeholder-gray-300 focus:border-gray-900 focus:outline-none focus:ring-0 dark:border-gray-600 dark:text-white dark:focus:border-white',
            textarea: 'w-full border-0 border-b-2 border-gray-200 bg-transparent px-0 py-2 text-sm text-gray-900 placeholder-gray-300 focus:border-gray-900 focus:outline-none focus:ring-0 dark:border-gray-600 dark:text-white',
            select: 'w-full border-0 border-b-2 border-gray-200 bg-transparent px-0 py-2 text-sm text-gray-900 focus:border-gray-900 focus:outline-none focus:ring-0 dark:border-gray-600 dark:text-white',
            checkbox: 'h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900',
            radio: 'h-4 w-4 border-gray-300 text-gray-900 focus:ring-gray-900',
            choice_label: 'flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300',
            button: 'inline-flex items-center border-b-2 border-gray-900 bg-transparent px-0 py-2 text-sm font-semibold text-gray-900 hover:border-blue-600 hover:text-blue-600 focus:outline-none transition-colors dark:border-white dark:text-white',
            error: 'mt-1 text-xs text-red-500 dark:text-red-400',
            hint: 'mt-1 text-xs text-gray-400',
            custom_css: '',
        },
    },
    {
        id: 'rounded',
        label: 'Rounded',
        styles: {
            form: '',
            wrapper: 'mb-5',
            label: 'block text-sm font-semibold text-gray-800 mb-1.5 dark:text-gray-200',
            input: 'w-full rounded-full border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500',
            textarea: 'w-full rounded-2xl border border-gray-200 bg-gray-50 px-4 py-3 text-sm text-gray-900 placeholder-gray-400 focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white',
            select: 'w-full rounded-full border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 focus:border-blue-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-200 dark:border-gray-700 dark:bg-gray-800 dark:text-white',
            checkbox: 'h-4 w-4 rounded border-gray-300 text-blue-500 focus:ring-blue-400',
            radio: 'h-4 w-4 border-gray-300 text-blue-500 focus:ring-blue-400',
            choice_label: 'flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300',
            button: 'inline-flex items-center rounded-full bg-blue-500 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2 transition-colors shadow-md dark:bg-blue-600 dark:hover:bg-blue-500',
            error: 'mt-1.5 text-sm text-red-500 dark:text-red-400',
            hint: 'mt-1 text-xs text-gray-400 dark:text-gray-500',
            custom_css: '',
        },
    },
    {
        id: 'dark',
        label: 'Dark',
        styles: {
            form: 'bg-gray-950 p-8 rounded-xl',
            wrapper: 'mb-5',
            label: 'block text-sm font-medium text-gray-300 mb-1',
            input: 'w-full rounded-lg border border-gray-700 bg-gray-900 px-3 py-2 text-sm text-gray-100 placeholder-gray-600 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500',
            textarea: 'w-full rounded-lg border border-gray-700 bg-gray-900 px-3 py-2 text-sm text-gray-100 placeholder-gray-600 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500',
            select: 'w-full rounded-lg border border-gray-700 bg-gray-900 px-3 py-2 text-sm text-gray-100 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500',
            checkbox: 'h-4 w-4 rounded border-gray-600 bg-gray-800 text-blue-500 focus:ring-blue-500',
            radio: 'h-4 w-4 border-gray-600 bg-gray-800 text-blue-500 focus:ring-blue-500',
            choice_label: 'flex items-center gap-2 text-sm text-gray-300',
            button: 'inline-flex items-center rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:ring-offset-gray-950 transition-colors',
            error: 'mt-1 text-sm text-red-400',
            hint: 'mt-1 text-xs text-gray-500',
            custom_css: '.flexible-form { color-scheme: dark; }',
        },
    },
];

const ELEMENTS = {
    layout: [
        { key: 'form',    label: 'Form wrapper',  placeholder: 'e.g. max-w-lg mx-auto' },
        { key: 'wrapper', label: 'Field wrapper',  placeholder: 'e.g. mb-5' },
    ],
    inputs: [
        { key: 'label',    label: 'Label',    placeholder: 'e.g. block text-sm font-medium text-gray-700' },
        { key: 'input',    label: 'Inputs',   placeholder: 'e.g. w-full rounded border px-3 py-2' },
        { key: 'textarea', label: 'Textarea', placeholder: 'e.g. w-full rounded border px-3 py-2' },
        { key: 'select',   label: 'Select',   placeholder: 'e.g. w-full rounded border px-3 py-2' },
        { key: 'hint',     label: 'Help text', placeholder: 'e.g. text-xs text-gray-500 mt-1' },
        { key: 'error',    label: 'Error',    placeholder: 'e.g. text-sm text-red-600 mt-1' },
    ],
    choices: [
        { key: 'checkbox',     label: 'Checkbox input',  placeholder: 'e.g. h-4 w-4 rounded text-blue-600' },
        { key: 'radio',        label: 'Radio input',     placeholder: 'e.g. h-4 w-4 text-blue-600' },
        { key: 'choice_label', label: 'Choice label',    placeholder: 'e.g. flex items-center gap-2 text-sm' },
    ],
    feedback: [
        { key: 'button', label: 'Submit button', placeholder: 'e.g. bg-blue-600 text-white px-5 py-2 rounded' },
    ],
};

let refreshTimer = null;

export default {
    props: {
        stylesUrl:     { type: String, required: true },
        stylesSaveUrl: { type: String, required: true },
        fields:        { type: Array, default: () => [] },
    },

    data() {
        return {
            loading:      true,
            saving:       false,
            docked:       true,
            previewMode:  'light',
            previewHtml:  '',
            styles: {
                form: '', wrapper: '', label: '', input: '', textarea: '',
                select: '', checkbox: '', radio: '', choice_label: '',
                button: '', error: '', hint: '', custom_css: '',
                preview_stylesheet: '',
            },
            presets:          PRESETS,
            layoutElements:   ELEMENTS.layout,
            inputElements:    ELEMENTS.inputs,
            choiceElements:   ELEMENTS.choices,
            feedbackElements: ELEMENTS.feedback,
            availableClasses: [
                { name: 'flexible-form',             description: 'The <form> element' },
                { name: 'flexible-form__field',      description: 'Each field wrapper div' },
                { name: 'flexible-form__label',      description: 'Field labels' },
                { name: 'flexible-form__instructions', description: 'Help/hint text below labels' },
                { name: 'flexible-form__input',      description: 'Text, email, number, date, time, URL inputs · textareas · selects' },
                { name: 'flexible-form__checkbox',   description: 'Checkbox inputs' },
                { name: 'flexible-form__radio',      description: 'Radio inputs' },
                { name: 'flexible-form__check-label', description: 'Checkbox/radio label wrappers' },
                { name: 'flexible-form__check-group', description: 'Container for checkbox/radio options' },
                { name: 'flexible-form__fieldset',   description: 'Fieldset for checkbox/radio groups' },
                { name: 'flexible-form__error-msg',  description: 'Validation error messages' },
                { name: 'flexible-form__button',     description: 'Submit button' },
                { name: 'flexible-form__submit',     description: 'Submit button wrapper div' },
                { name: 'flexible-form__input--error', description: 'Input modifier applied when a field has a validation error' },
            ],
        };
    },

    async mounted() {
        this._onSave = (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 's') {
                e.preventDefault();
                this.save();
            }
        };
        document.addEventListener('keydown', this._onSave);

        try {
            const { data } = await this.$axios.get(this.stylesUrl);
            this.styles = { ...this.styles, ...data };
        } catch {
            //
        } finally {
            this.loading = false;
        }
        await this.$nextTick();
        this.createEditor(this.$refs.cssEditorMount);
        this.refreshPreview();
    },

    beforeUnmount() {
        document.removeEventListener('keydown', this._onSave);
        this.editorView?.destroy();
    },

    methods: {
        createEditor(container) {
            if (!container) return;
            const cpIsDark = document.documentElement.classList.contains('dark');
            const extensions = [
                basicSetup,
                cssLang(),
                keymap.of([{ key: 'Mod-s', run: () => { this.save(); return true; } }]),
                EditorView.updateListener.of(update => {
                    if (update.docChanged) {
                        this.styles.custom_css = update.state.doc.toString();
                        this.debouncedRefresh();
                    }
                }),
                EditorView.theme({
                    '&': { fontSize: '12.5px', fontFamily: 'ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace' },
                }),
            ];
            if (!this.docked || cpIsDark) extensions.push(oneDark);
            this.editorView = new EditorView({
                state: EditorState.create({ doc: this.styles.custom_css || '', extensions }),
                parent: container,
            });
        },

        async toggleDock() {
            if (this.editorView) {
                this.styles.custom_css = this.editorView.state.doc.toString();
                this.editorView.destroy();
                this.editorView = null;
            }
            this.docked = !this.docked;
            await this.$nextTick();
            this.createEditor(this.docked ? this.$refs.cssEditorMount : this.$refs.cssEditorFloatMount);
        },

        applyPreset(preset) {
            this.styles = { ...preset.styles };
            if (this.editorView) {
                const current = this.editorView.state.doc.toString();
                const next = this.styles.custom_css || '';
                if (current !== next) {
                    this.editorView.dispatch({ changes: { from: 0, to: current.length, insert: next } });
                }
            }
            this.refreshPreview();
        },

        debouncedRefresh() {
            clearTimeout(refreshTimer);
            refreshTimer = setTimeout(() => this.refreshPreview(), 350);
        },

        refreshPreview() {
            const s   = this.styles;
            const cls = (base, key) => (base + ' ' + (s[key] || '')).trim();
            const dark = this.previewMode === 'dark';

            const bodyBg  = dark ? '#111827' : '#f3f4f6';
            const darkAttr = dark ? ' class="dark"' : '';

            // Build mock form HTML for the preview iframe
            const formHtml = `
<form class="${cls('flexible-form', 'form')}">

  <div class="${cls('flexible-form__field', 'wrapper')}">
    <label class="${cls('flexible-form__label', 'label')}">Your Name</label>
    <input type="text" class="${cls('flexible-form__input', 'input')}" placeholder="Jane Smith">
  </div>

  <div class="${cls('flexible-form__field', 'wrapper')}">
    <label class="${cls('flexible-form__label', 'label')}">Email Address</label>
    <p class="${cls('flexible-form__instructions', 'hint')}">We'll never share your email.</p>
    <input type="email" class="${cls('flexible-form__input', 'input')}" placeholder="jane@example.com">
  </div>

  <div class="${cls('flexible-form__field', 'wrapper')}">
    <label class="${cls('flexible-form__label', 'label')}">Date of Birth</label>
    <input type="date" class="${cls('flexible-form__input', 'input')}">
  </div>

  <div class="${cls('flexible-form__field', 'wrapper')}">
    <label class="${cls('flexible-form__label', 'label')}">Message</label>
    <textarea class="${cls('flexible-form__input', 'textarea')}" rows="3" placeholder="Your message…"></textarea>
  </div>

  <div class="${cls('flexible-form__field', 'wrapper')}">
    <label class="${cls('flexible-form__label', 'label')}">How did you hear about us?</label>
    <select class="${cls('flexible-form__input', 'select')}">
      <option value="">Please select…</option>
      <option>Google</option>
      <option>Social media</option>
      <option>Word of mouth</option>
    </select>
  </div>

  <div class="${cls('flexible-form__field', 'wrapper')}">
    <fieldset class="flexible-form__fieldset">
      <legend class="${cls('flexible-form__label', 'label')}">Interests</legend>
      <div class="flexible-form__check-group" style="display:flex;flex-direction:column;gap:8px;margin-top:6px">
        <label class="${cls('flexible-form__check-label', 'choice_label')}">
          <input type="checkbox" class="${cls('flexible-form__checkbox', 'checkbox')}"> Design
        </label>
        <label class="${cls('flexible-form__check-label', 'choice_label')}">
          <input type="checkbox" class="${cls('flexible-form__checkbox', 'checkbox')}" checked> Development
        </label>
      </div>
    </fieldset>
  </div>

  <div class="${cls('flexible-form__field', 'wrapper')}">
    <fieldset class="flexible-form__fieldset">
      <legend class="${cls('flexible-form__label', 'label')}">Contact preference <span style="color:red">*</span></legend>
      <div class="flexible-form__check-group" style="display:flex;flex-direction:column;gap:8px;margin-top:6px">
        <label class="${cls('flexible-form__check-label', 'choice_label')}">
          <input type="radio" name="pref" class="${cls('flexible-form__radio', 'radio')}" checked> Email
        </label>
        <label class="${cls('flexible-form__check-label', 'choice_label')}">
          <input type="radio" name="pref" class="${cls('flexible-form__radio', 'radio')}"> Phone
        </label>
      </div>
    </fieldset>
  </div>

  <div class="${cls('flexible-form__field', 'wrapper')}">
    <label class="${cls('flexible-form__label', 'label')}">Phone <span style="color:red">*</span></label>
    <input type="tel" class="${cls('flexible-form__input', 'input')} flexible-form__input--error" placeholder="+44 7700 …">
    <p class="${cls('flexible-form__error-msg', 'error')}">This field is required.</p>
  </div>

  <div class="flexible-form__submit">
    <button type="button" class="${cls('flexible-form__button', 'button')}">Submit</button>
  </div>

</form>`;

            this.previewHtml = `<!DOCTYPE html>
<html${darkAttr}>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<script src="https://cdn.tailwindcss.com"><\/script>
<script>tailwind.config = { darkMode: 'class' }<\/script>
${s.preview_stylesheet ? `<link rel="stylesheet" href="${s.preview_stylesheet}">` : ''}
<style>
* { box-sizing: border-box; }
body { padding: 28px; background: ${bodyBg}; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; transition: background .2s; }
.flexible-form__field { margin-bottom: 16px; }
.flexible-form__label { display: block; margin-bottom: 4px; }
.flexible-form__instructions { margin-top: 0; margin-bottom: 6px; }
.flexible-form__fieldset { border: none; padding: 0; margin: 0; }
.flexible-form__submit { margin-top: 8px; }
.flexible-form__required { color: red; margin-left: 2px; }
${s.custom_css || ''}
</style>
</head>
<body>
${formHtml}
</body>
</html>`;
        },

        insertClass(className) {
            if (!this.editorView) return;
            const state = this.editorView.state;
            const from = state.selection.main.head;
            const before = state.doc.sliceString(0, from);
            const prefix = before.length > 0 && !before.endsWith('\n') ? '\n\n' : '';
            const snippet = `${prefix}.${className} {\n  \n}`;
            const newCursor = from + prefix.length + `.${className} {\n  `.length;
            this.editorView.dispatch({
                changes: { from, insert: snippet },
                selection: { anchor: newCursor },
            });
            this.editorView.focus();
            this.debouncedRefresh();
        },

        async save() {
            this.saving = true;
            try {
                await this.$axios.post(this.stylesSaveUrl, this.styles);
                this.$toast.success('Styles saved');
            } catch {
                this.$toast.error('Could not save styles.');
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
