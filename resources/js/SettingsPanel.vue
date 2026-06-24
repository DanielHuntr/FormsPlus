<template>
    <div class="ff-settings">
        <div v-if="loading" class="ff-settings__loading">Loading settings…</div>

        <form v-else @submit.prevent="save" class="ff-settings__form">

            <!-- Form behaviour -->
            <section class="ff-settings__section">
                <h3 class="ff-settings__section-title">Form Behaviour</h3>

                <div class="ff-settings__row">
                    <label class="ff-settings__label">Form enabled</label>
                    <div class="ff-settings__control">
                        <label class="ff-toggle">
                            <input type="checkbox" v-model="form.enabled" class="ff-toggle__input">
                            <span class="ff-toggle__track"></span>
                        </label>
                        <p class="ff-hint">Disable to prevent new submissions without removing the form from your site.</p>
                    </div>
                </div>

                <div class="ff-settings__row">
                    <label class="ff-settings__label">Submit button label</label>
                    <div class="ff-settings__control">
                        <input v-model="form.submit_label" type="text" class="ff-input" placeholder="Submit">
                    </div>
                </div>
            </section>

            <!-- After submission -->
            <section class="ff-settings__section">
                <h3 class="ff-settings__section-title">After Submission</h3>

                <div class="ff-settings__row">
                    <label class="ff-settings__label">On success</label>
                    <div class="ff-settings__control ff-settings__radio-group">
                        <label class="ff-settings__radio-label">
                            <input type="radio" v-model="form.on_submit" value="message">
                            Show a success message
                        </label>
                        <label class="ff-settings__radio-label">
                            <input type="radio" v-model="form.on_submit" value="redirect">
                            Redirect to a page
                        </label>
                    </div>
                </div>

                <template v-if="form.on_submit === 'message'">
                    <div class="ff-settings__row">
                        <label class="ff-settings__label">Success title</label>
                        <div class="ff-settings__control">
                            <input v-model="form.success_title" type="text" class="ff-input" placeholder="Message sent!">
                        </div>
                    </div>
                    <div class="ff-settings__row">
                        <label class="ff-settings__label">Success message</label>
                        <div class="ff-settings__control">
                            <textarea v-model="form.success_message" class="ff-input ff-input--textarea" rows="3" placeholder="Thank you for getting in touch…"></textarea>
                        </div>
                    </div>
                </template>

                <template v-if="form.on_submit === 'redirect'">
                    <div class="ff-settings__row">
                        <label class="ff-settings__label">Redirect to</label>
                        <div class="ff-settings__control">
                            <input v-model="form.redirect_url" type="text" class="ff-input" placeholder="/thank-you">
                            <p class="ff-hint">Enter a URL or path (e.g. <code>/thank-you</code>).</p>
                        </div>
                    </div>
                </template>
            </section>

            <!-- Email notifications -->
            <section class="ff-settings__section">
                <h3 class="ff-settings__section-title">Email Notifications</h3>

                <div class="ff-settings__row">
                    <label class="ff-settings__label">Send notification to</label>
                    <div class="ff-settings__control">
                        <input v-model="form.notification_email" type="email" class="ff-input" placeholder="you@example.com">
                        <p class="ff-hint">Leave empty to disable email notifications.</p>
                    </div>
                </div>

                <div class="ff-settings__row" v-if="form.notification_email && emailFields.length">
                    <label class="ff-settings__label">Reply-to field</label>
                    <div class="ff-settings__control">
                        <select v-model="form.reply_to_field" class="ff-input ff-input--select">
                            <option value="">— None —</option>
                            <option v-for="f in emailFields" :key="f.handle" :value="f.handle">
                                {{ f.display || f.handle }}
                            </option>
                        </select>
                        <p class="ff-hint">Use this field's value as the reply-to address in notification emails.</p>
                    </div>
                </div>

                <div class="ff-settings__row" v-if="form.notification_email">
                    <label class="ff-settings__label">Confirmation email</label>
                    <div class="ff-settings__control">
                        <label class="ff-toggle">
                            <input type="checkbox" v-model="form.confirmation_email_enabled" class="ff-toggle__input">
                            <span class="ff-toggle__track"></span>
                        </label>
                        <p class="ff-hint">Send a confirmation email to the person who submitted the form.</p>
                    </div>
                </div>

                <div class="ff-settings__row" v-if="form.notification_email && form.confirmation_email_enabled && emailFields.length">
                    <label class="ff-settings__label">Submitter email field</label>
                    <div class="ff-settings__control">
                        <select v-model="form.confirmation_email_field" class="ff-input ff-input--select">
                            <option value="">— Select a field —</option>
                            <option v-for="f in emailFields" :key="f.handle" :value="f.handle">
                                {{ f.display || f.handle }}
                            </option>
                        </select>
                        <p class="ff-hint">The form field that contains the submitter's email address. Configure the confirmation email template in the Email tab.</p>
                    </div>
                </div>
            </section>

            <!-- Actions (hidden when embedded inside FormBuilder) -->
            <div v-if="standalone" class="ff-settings__footer">
                <p v-if="saveMessage" class="ff-settings__save-msg" :class="saveError ? 'ff-settings__save-msg--error' : 'ff-settings__save-msg--ok'">
                    {{ saveMessage }}
                </p>
                <button type="submit" class="ff-btn ff-btn--primary" :disabled="saving">
                    {{ saving ? 'Saving…' : 'Save Settings' }}
                </button>
            </div>
        </form>
    </div>
</template>

<script>
export default {
    props: {
        settingsUrl:     { type: String, required: true },
        settingsSaveUrl: { type: String, required: true },
        fields:          { type: Array, default: () => [] },
        standalone:      { type: Boolean, default: true },
    },

    data() {
        return {
            loading:     true,
            saving:      false,
            saveMessage: '',
            saveError:   false,
            form: {
                enabled:              true,
                submit_label:         'Submit',
                notification_email:   '',
                reply_to_field:       '',
                on_submit:            'message',
                success_title:        'Message sent!',
                success_message:      "Thank you for getting in touch. We'll be in touch soon.",
                redirect_url:               '',
                confirmation_email_enabled: false,
                confirmation_email_field:   '',
            },
        };
    },

    computed: {
        emailFields() {
            return this.fields.filter(
                f => f.type === 'text' && f.input_type === 'email'
            );
        },
    },

    watch: {
        form: {
            deep: true,
            handler() {
                if (this._loaded) this.$emit('dirty');
            },
        },
    },

    async mounted() {
        try {
            const { data } = await this.$axios.get(this.settingsUrl);
            this.form = { ...this.form, ...data };

            // Auto-select the only email field when nothing is saved yet
            if (this.emailFields.length === 1) {
                if (!this.form.reply_to_field) {
                    this.form.reply_to_field = this.emailFields[0].handle;
                }
                if (!this.form.confirmation_email_field) {
                    this.form.confirmation_email_field = this.emailFields[0].handle;
                }
            }
        } catch {
            // Keep defaults if load fails
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
        async save() {
            this.saving      = true;
            this.saveMessage = '';
            this.saveError   = false;

            try {
                const { data } = await this.$axios.post(this.settingsSaveUrl, this.form);
                if (data.success) {
                    this.saveMessage = 'Settings saved.';
                    setTimeout(() => { this.saveMessage = ''; }, 3000);
                } else {
                    this.saveMessage = data.message || 'Could not save settings.';
                    this.saveError   = true;
                }
            } catch (error) {
                this.saveMessage = error.response?.data?.message || 'Network error. Please try again.';
                this.saveError   = true;
            } finally {
                this.saving = false;
            }
        },
    },
};
</script>
