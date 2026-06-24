<template>
    <div class="etb">
        <div v-if="loading" class="etb__loading">Loading template…</div>

        <div v-else class="etb__wrap">

            <!-- Use-default toggle (per-form only) -->
            <div v-if="showUseDefault" class="etb__use-default-bar">
                <label class="ff-toggle">
                    <input type="checkbox" v-model="useDefault" class="ff-toggle__input">
                    <span class="ff-toggle__track"></span>
                </label>
                <span class="etb__use-default-label">Use the default template for this form</span>
            </div>

            <!-- Three-column layout (hidden when using default) -->
            <div v-if="!showUseDefault || !useDefault" class="etb__layout">

                <!-- LEFT: Palette -->
                <aside class="etb__palette">
                    <div class="etb__palette-section-title">Blocks</div>
                    <div
                        v-for="bt in blockTypes"
                        :key="bt.type"
                        class="etb__palette-item"
                        draggable="true"
                        @dragstart="onPaletteDragStart(bt.type)"
                        @dragend="resetDrag"
                        @click="addBlockAtEnd(bt.type)"
                        :title="'Add ' + bt.label"
                    >
                        <span class="etb__palette-icon" v-html="bt.icon"></span>
                        <span class="etb__palette-label">{{ bt.label }}</span>
                    </div>

                    <div class="etb__palette-section-title" style="margin-top:20px">Variables</div>
                    <div v-for="v in variableList" :key="v.key" class="etb__var">
                        <code class="etb__var-code" @click="copyVar(v.key)" :title="'Click to copy'">{{ varToken(v.key) }}</code>
                        <span class="etb__var-desc">{{ v.desc }}</span>
                    </div>
                </aside>

                <!-- CENTER: Canvas -->
                <main
                    class="etb__canvas"
                    @dragover.prevent="onCanvasDragOver"
                    @drop.prevent="dropAt(template.blocks.length)"
                    @dragenter.prevent
                >
                    <!-- Subject line -->
                    <div class="etb__subject-row">
                        <label class="etb__subject-label">Subject line</label>
                        <input
                            v-model="template.subject"
                            type="text"
                            class="ff-input etb__subject-input"
                            placeholder="Email subject…"
                        >
                    </div>

                    <!-- Blocks -->
                    <div class="etb__block-list">
                        <div v-if="template.blocks.length === 0" class="etb__canvas-empty">
                            Drag blocks from the left panel, or click to add
                        </div>

                        <template v-for="(block, i) in template.blocks" :key="i">
                            <!-- Drop zone before this block -->
                            <div
                                class="etb__drop-zone"
                                :class="{ 'etb__drop-zone--active': dropIndex === i }"
                                @dragover.prevent="dropIndex = i"
                                @dragleave="onDropZoneLeave(i)"
                                @drop.prevent="dropAt(i)"
                            ></div>

                            <!-- Block row -->
                            <div
                                class="etb__block"
                                :class="{
                                    'etb__block--selected': selectedIndex === i,
                                    'etb__block--dragging': draggingCanvas === i,
                                }"
                                draggable="true"
                                @dragstart="onCanvasDragStart(i)"
                                @dragend="resetDrag"
                                @click.stop="selectedIndex = (selectedIndex === i ? null : i)"
                            >
                                <div class="etb__block-handle" title="Drag to reorder">
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="5" r="1.5" fill="currentColor"/><circle cx="9" cy="12" r="1.5" fill="currentColor"/><circle cx="9" cy="19" r="1.5" fill="currentColor"/><circle cx="15" cy="5" r="1.5" fill="currentColor"/><circle cx="15" cy="12" r="1.5" fill="currentColor"/><circle cx="15" cy="19" r="1.5" fill="currentColor"/></svg>
                                </div>
                                <div class="etb__block-info">
                                    <span class="etb__block-type-badge">{{ labelFor(block.type) }}</span>
                                    <span class="etb__block-excerpt">{{ excerptFor(block) }}</span>
                                </div>
                                <button
                                    class="etb__block-del"
                                    @click.stop="deleteBlock(i)"
                                    title="Remove block"
                                >
                                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                                </button>
                            </div>
                        </template>

                        <!-- Drop zone after last block -->
                        <div
                            class="etb__drop-zone etb__drop-zone--last"
                            :class="{ 'etb__drop-zone--active': dropIndex === template.blocks.length }"
                            @dragover.prevent="dropIndex = template.blocks.length"
                            @dragleave="onDropZoneLeave(template.blocks.length)"
                            @drop.prevent="dropAt(template.blocks.length)"
                        ></div>
                    </div>

                    <!-- Save row (save button hidden when embedded in FormBuilder) -->
                    <div class="etb__save-row">
                        <button class="ff-btn ff-btn--ghost" :disabled="previewing" @click="openPreview">
                            {{ previewing ? 'Loading…' : 'Preview' }}
                        </button>
                        <button v-if="standalone" class="ff-btn ff-btn--primary" :disabled="saving" @click="save">
                            {{ saving ? 'Saving…' : 'Save Template' }}
                        </button>
                    </div>
                </main>

                <!-- RIGHT: Block property editor -->
                <aside class="etb__editor">
                    <template v-if="selectedBlock">
                        <div class="etb__editor-header">
                            <span class="etb__editor-title">{{ labelFor(selectedBlock.type) }}</span>
                            <button class="etb__editor-close" @click="selectedIndex = null" title="Close">
                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                            </button>
                        </div>

                        <!-- Logo -->
                        <template v-if="selectedBlock.type === 'logo'">
                            <div class="etb__field">
                                <label class="etb__field-label">Image URL</label>
                                <input v-model="selectedBlock.image_url" type="text" class="ff-input" placeholder="https://…/logo.png">
                            </div>
                            <div class="etb__field">
                                <label class="etb__field-label">Alt text</label>
                                <input v-model="selectedBlock.alt" type="text" class="ff-input" placeholder="Company logo">
                            </div>
                            <div class="etb__field">
                                <label class="etb__field-label">Width (px)</label>
                                <input v-model.number="selectedBlock.width" type="number" class="ff-input" min="40" max="560">
                            </div>
                            <div class="etb__field">
                                <label class="etb__field-label">Link URL</label>
                                <input v-model="selectedBlock.link_url" type="text" class="ff-input" placeholder="https://…">
                            </div>
                            <div class="etb__field">
                                <label class="etb__field-label">Alignment</label>
                                <select v-model="selectedBlock.align" class="ff-input ff-input--select">
                                    <option value="left">Left</option>
                                    <option value="center">Center</option>
                                    <option value="right">Right</option>
                                </select>
                            </div>
                        </template>

                        <!-- Heading -->
                        <template v-if="selectedBlock.type === 'heading'">
                            <div class="etb__field">
                                <label class="etb__field-label">Text</label>
                                <input v-model="selectedBlock.text" type="text" class="ff-input">
                            </div>
                            <div class="etb__field">
                                <label class="etb__field-label">Level</label>
                                <select v-model="selectedBlock.level" class="ff-input ff-input--select">
                                    <option value="h1">H1 — Large</option>
                                    <option value="h2">H2 — Medium</option>
                                    <option value="h3">H3 — Small</option>
                                </select>
                            </div>
                            <div class="etb__field">
                                <label class="etb__field-label">Alignment</label>
                                <select v-model="selectedBlock.align" class="ff-input ff-input--select">
                                    <option value="left">Left</option>
                                    <option value="center">Center</option>
                                    <option value="right">Right</option>
                                </select>
                            </div>
                            <div class="etb__field">
                                <label class="etb__field-label">Color</label>
                                <div class="etb__color-row">
                                    <input v-model="selectedBlock.color" type="color" class="etb__color-swatch">
                                    <input v-model="selectedBlock.color" type="text" class="ff-input etb__color-hex" placeholder="#18181b">
                                </div>
                            </div>
                        </template>

                        <!-- Text -->
                        <template v-if="selectedBlock.type === 'text'">
                            <div class="etb__field">
                                <label class="etb__field-label">Content</label>
                                <textarea v-model="selectedBlock.content" class="ff-input ff-input--textarea" rows="6" placeholder="Your text here…"></textarea>
                                <p class="ff-hint">Use {{ varExample }} syntax for dynamic values.</p>
                            </div>
                            <div class="etb__field">
                                <label class="etb__field-label">Alignment</label>
                                <select v-model="selectedBlock.align" class="ff-input ff-input--select">
                                    <option value="left">Left</option>
                                    <option value="center">Center</option>
                                    <option value="right">Right</option>
                                </select>
                            </div>
                            <div class="etb__field">
                                <label class="etb__field-label">Color</label>
                                <div class="etb__color-row">
                                    <input v-model="selectedBlock.color" type="color" class="etb__color-swatch">
                                    <input v-model="selectedBlock.color" type="text" class="ff-input etb__color-hex" placeholder="#374151">
                                </div>
                            </div>
                        </template>

                        <!-- Button -->
                        <template v-if="selectedBlock.type === 'button'">
                            <div class="etb__field">
                                <label class="etb__field-label">Label</label>
                                <input v-model="selectedBlock.label" type="text" class="ff-input" placeholder="Click here">
                            </div>
                            <div class="etb__field">
                                <label class="etb__field-label">URL</label>
                                <input v-model="selectedBlock.url" type="text" class="ff-input" placeholder="https://…">
                            </div>
                            <div class="etb__field">
                                <label class="etb__field-label">Alignment</label>
                                <select v-model="selectedBlock.align" class="ff-input ff-input--select">
                                    <option value="left">Left</option>
                                    <option value="center">Center</option>
                                    <option value="right">Right</option>
                                </select>
                            </div>
                            <div class="etb__field">
                                <label class="etb__field-label">Background</label>
                                <div class="etb__color-row">
                                    <input v-model="selectedBlock.bg_color" type="color" class="etb__color-swatch">
                                    <input v-model="selectedBlock.bg_color" type="text" class="ff-input etb__color-hex" placeholder="#3b82f6">
                                </div>
                            </div>
                            <div class="etb__field">
                                <label class="etb__field-label">Text color</label>
                                <div class="etb__color-row">
                                    <input v-model="selectedBlock.text_color" type="color" class="etb__color-swatch">
                                    <input v-model="selectedBlock.text_color" type="text" class="ff-input etb__color-hex" placeholder="#ffffff">
                                </div>
                            </div>
                        </template>

                        <!-- Divider -->
                        <template v-if="selectedBlock.type === 'divider'">
                            <div class="etb__field">
                                <label class="etb__field-label">Color</label>
                                <div class="etb__color-row">
                                    <input v-model="selectedBlock.color" type="color" class="etb__color-swatch">
                                    <input v-model="selectedBlock.color" type="text" class="ff-input etb__color-hex" placeholder="#e4e4e7">
                                </div>
                            </div>
                        </template>

                        <!-- Submission Data -->
                        <template v-if="selectedBlock.type === 'submission_data'">
                            <div class="etb__field">
                                <label class="etb__field-label">Section title</label>
                                <input v-model="selectedBlock.title" type="text" class="ff-input" placeholder="Submission Details">
                            </div>
                            <div class="etb__field etb__field--toggle">
                                <label class="ff-toggle">
                                    <input type="checkbox" v-model="selectedBlock.show_title" class="ff-toggle__input">
                                    <span class="ff-toggle__track"></span>
                                </label>
                                <span class="etb__field-label">Show section title</span>
                            </div>
                            <p class="ff-hint" style="padding:0 16px">Displays all submitted form fields in a table. Only relevant in notification emails.</p>
                        </template>

                        <!-- Spacer -->
                        <template v-if="selectedBlock.type === 'spacer'">
                            <div class="etb__field">
                                <label class="etb__field-label">Height (px)</label>
                                <input v-model.number="selectedBlock.height" type="number" class="ff-input" min="4" max="120">
                            </div>
                        </template>

                        <!-- Footer -->
                        <template v-if="selectedBlock.type === 'footer'">
                            <div class="etb__field">
                                <label class="etb__field-label">Content</label>
                                <textarea v-model="selectedBlock.content" class="ff-input ff-input--textarea" rows="4" placeholder="Footer text…"></textarea>
                            </div>
                            <div class="etb__field">
                                <label class="etb__field-label">Alignment</label>
                                <select v-model="selectedBlock.align" class="ff-input ff-input--select">
                                    <option value="left">Left</option>
                                    <option value="center">Center</option>
                                    <option value="right">Right</option>
                                </select>
                            </div>
                            <div class="etb__field">
                                <label class="etb__field-label">Color</label>
                                <div class="etb__color-row">
                                    <input v-model="selectedBlock.color" type="color" class="etb__color-swatch">
                                    <input v-model="selectedBlock.color" type="text" class="ff-input etb__color-hex" placeholder="#a1a1aa">
                                </div>
                            </div>
                        </template>
                    </template>

                    <div v-else class="etb__editor-empty">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="margin-bottom:8px;opacity:.4"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                        <p>Click a block to edit its properties</p>
                    </div>
                </aside>
            </div>

            <!-- Using default — show info + save button for the toggle -->
            <div v-if="showUseDefault && useDefault" class="etb__using-default-info">
                <p>This form uses the <strong>default email template</strong>. Toggle off above to create a custom template for this form.</p>
                <div class="etb__save-row" style="border-top:none;padding-top:0">
                    <span
                        v-if="saveMessage"
                        class="etb__save-msg"
                        :class="saveError ? 'etb__save-msg--error' : 'etb__save-msg--ok'"
                    >{{ saveMessage }}</span>
                    <button class="ff-btn ff-btn--primary" :disabled="saving" @click="save">
                        {{ saving ? 'Saving…' : 'Save Settings' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Email preview modal -->
        <div v-if="previewOpen" class="etb__preview-overlay" @click.self="previewOpen = false">
            <div class="etb__preview-modal">
                <div class="etb__preview-header">
                    <span class="etb__preview-title">Email Preview</span>
                    <button class="etb__preview-close" @click="previewOpen = false" title="Close">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <iframe
                    v-if="previewHtml"
                    class="etb__preview-frame"
                    :srcdoc="previewHtml"
                    frameborder="0"
                    title="Email Preview"
                ></iframe>
            </div>
        </div>
    </div>
</template>

<script>
const BLOCK_TYPES = [
    {
        type: 'logo',
        label: 'Logo',
        icon: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>',
    },
    {
        type: 'heading',
        label: 'Heading',
        icon: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="4 7 4 4 20 4 20 7"/><line x1="9" y1="20" x2="15" y2="20"/><line x1="12" y1="4" x2="12" y2="20"/></svg>',
    },
    {
        type: 'text',
        label: 'Text',
        icon: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="21" y1="10" x2="3" y2="10"/><line x1="21" y1="6" x2="3" y2="6"/><line x1="21" y1="14" x2="3" y2="14"/><line x1="21" y1="18" x2="11" y2="18"/></svg>',
    },
    {
        type: 'button',
        label: 'Button',
        icon: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="7" width="20" height="10" rx="3"/><line x1="8" y1="12" x2="16" y2="12"/></svg>',
    },
    {
        type: 'divider',
        label: 'Divider',
        icon: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="2" y1="12" x2="22" y2="12"/></svg>',
    },
    {
        type: 'submission_data',
        label: 'Form Data',
        icon: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="9" x2="9" y2="21"/></svg>',
    },
    {
        type: 'spacer',
        label: 'Spacer',
        icon: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="3" x2="12" y2="21"/><polyline points="8 7 12 3 16 7"/><polyline points="8 17 12 21 16 17"/></svg>',
    },
    {
        type: 'footer',
        label: 'Footer',
        icon: '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/><line x1="3" y1="10" x2="21" y2="10"/><line x1="3" y1="14" x2="21" y2="14"/></svg>',
    },
];

const BLOCK_DEFAULTS = {
    logo:            { type: 'logo',            image_url: '', alt: '',                   width: 160, align: 'left',   link_url: '' },
    heading:         { type: 'heading',         text: 'Your Heading',                      level: 'h2', align: 'left',  color: '#18181b' },
    text:            { type: 'text',            content: 'Your text here.',               align: 'left',   color: '#374151' },
    button:          { type: 'button',          label: 'Click here',  url: '',            align: 'center', bg_color: '#3b82f6', text_color: '#ffffff' },
    divider:         { type: 'divider',         color: '#e4e4e7' },
    submission_data: { type: 'submission_data', title: 'Submission Details',              show_title: true },
    spacer:          { type: 'spacer',          height: 24 },
    footer:          { type: 'footer',          content: 'Sent by Forms Plus · {{site_name}}', align: 'center', color: '#a1a1aa' },
};

const BLOCK_LABELS = {
    logo: 'Logo', heading: 'Heading', text: 'Text', button: 'Button',
    divider: 'Divider', submission_data: 'Form Data', spacer: 'Spacer', footer: 'Footer',
};

export default {
    props: {
        apiUrl:         { type: String, required: true },
        previewApiUrl:  { type: String, required: true },
        showUseDefault: { type: Boolean, default: false },
        standalone:     { type: Boolean, default: true },
    },

    data() {
        return {
            loading:    true,
            saving:     false,
            useDefault: true,
            template:       { subject: '', blocks: [] },
            selectedIndex:  null,
            // preview
            previewing:   false,
            previewOpen:  false,
            previewHtml:  '',
            // drag state
            draggingPalette: null,
            draggingCanvas:  null,
            dropIndex:       null,
            blockTypes:      BLOCK_TYPES,
            variableList: [
                { key: 'form_title',    desc: 'Form title' },
                { key: 'site_name',     desc: 'Site name' },
                { key: 'submitted_at',  desc: 'Submission date/time' },
                { key: 'submission_id', desc: 'Submission ID' },
            ],
        };
    },

    computed: {
        selectedBlock() {
            return this.selectedIndex !== null ? this.template.blocks[this.selectedIndex] : null;
        },
        varExample() {
            return '{{variable}}';
        },
    },

    watch: {
        template: {
            deep: true,
            handler() {
                if (this._loaded) this.$emit('dirty');
            },
        },
    },

    async mounted() {
        try {
            const { data } = await this.$axios.get(this.apiUrl);
            this.template   = data.template ?? { subject: '', blocks: [] };
            this.useDefault = data.use_default !== undefined ? !!data.use_default : false;
        } catch {
            //
        } finally {
            this.loading = false;
        }

        this._loaded = true;

        if (this.standalone) {
            this._onSave = (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 's') {
                    e.preventDefault();
                    this.save();
                }
            };
            document.addEventListener('keydown', this._onSave);
        }
    },

    beforeUnmount() {
        if (this._onSave) document.removeEventListener('keydown', this._onSave);
    },

    methods: {
        // ── Drag from palette ──────────────────────────────────────────────
        onPaletteDragStart(type) {
            this.draggingPalette = type;
            this.draggingCanvas  = null;
        },

        // ── Drag from canvas ───────────────────────────────────────────────
        onCanvasDragStart(i) {
            this.draggingCanvas  = i;
            this.draggingPalette = null;
        },

        onCanvasDragOver() {
            // Fired on the canvas element itself (outside any drop zone)
            // We keep dropIndex as-is so the last zone stays active
        },

        onDropZoneLeave(i) {
            if (this.dropIndex === i) this.dropIndex = null;
        },

        resetDrag() {
            this.draggingPalette = null;
            this.draggingCanvas  = null;
            this.dropIndex       = null;
        },

        // ── Drop handling ──────────────────────────────────────────────────
        dropAt(index) {
            if (this.draggingPalette !== null) {
                const block = { ...BLOCK_DEFAULTS[this.draggingPalette] };
                this.template.blocks.splice(index, 0, block);
                this.selectedIndex = index;
            } else if (this.draggingCanvas !== null && this.draggingCanvas !== index) {
                const block    = this.template.blocks.splice(this.draggingCanvas, 1)[0];
                const target   = this.draggingCanvas < index ? index - 1 : index;
                this.template.blocks.splice(target, 0, block);
                if (this.selectedIndex === this.draggingCanvas) this.selectedIndex = target;
            }
            this.resetDrag();
        },

        // ── Block actions ──────────────────────────────────────────────────
        addBlockAtEnd(type) {
            const block = { ...BLOCK_DEFAULTS[type] };
            this.template.blocks.push(block);
            this.selectedIndex = this.template.blocks.length - 1;
        },

        deleteBlock(i) {
            this.template.blocks.splice(i, 1);
            if (this.selectedIndex === i)         this.selectedIndex = null;
            else if (this.selectedIndex > i)      this.selectedIndex--;
        },

        // ── Save ───────────────────────────────────────────────────────────
        async save() {
            this.saving = true;
            const body = { template: this.template };
            if (this.showUseDefault) body.use_default = this.useDefault;
            try {
                await this.$axios.post(this.apiUrl, body);
            } catch {
                this.$toast.error('Could not save template.');
                throw new Error('template save failed');
            } finally {
                this.saving = false;
            }
        },

        // ── Helpers ────────────────────────────────────────────────────────
        labelFor(type) {
            return BLOCK_LABELS[type] ?? type;
        },

        excerptFor(block) {
            const t = block.type;
            if (t === 'heading')         return block.text?.substring(0, 50) ?? '';
            if (t === 'text')            return block.content?.substring(0, 60) ?? '';
            if (t === 'button')          return block.label ?? '';
            if (t === 'footer')          return block.content?.substring(0, 60) ?? '';
            if (t === 'submission_data') return block.show_title ? block.title : '';
            if (t === 'spacer')          return `${block.height}px`;
            if (t === 'logo')            return block.image_url ? 'Image set' : 'No image';
            return '';
        },

        varToken(key) {
            return '{{' + key + '}}';
        },

        copyVar(key) {
            navigator.clipboard?.writeText('{{' + key + '}}').catch(() => {});
        },

        async openPreview() {
            this.previewing = true;
            try {
                const { data } = await this.$axios.post(this.previewApiUrl, { template: this.template });
                this.previewHtml = data.html;
                this.previewOpen = true;
            } catch {
                //
            } finally {
                this.previewing = false;
            }
        },
    },
};
</script>
