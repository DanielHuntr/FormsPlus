<template>
    <div class="ff-wrap">
        <header class="ff-header">
            <div>
                <h1 class="ff-title">Mail Settings</h1>
                <p class="ff-subtitle">Configure the SMTP account Forms Plus uses to send notification and confirmation emails.</p>
            </div>
        </header>

        <div v-if="loading" class="ff-settings__loading">Loading…</div>

        <div v-else class="ff-mail">

            <!-- Enable toggle -->
            <section class="ff-settings__section">
                <h3 class="ff-settings__section-title">Forms Plus Mail</h3>

                <div class="ff-settings__row">
                    <label class="ff-settings__label">Use custom mail settings</label>
                    <div class="ff-settings__control">
                        <label class="ff-toggle">
                            <input type="checkbox" v-model="form.enabled" class="ff-toggle__input">
                            <span class="ff-toggle__track"></span>
                        </label>
                        <p class="ff-hint">When enabled, Forms Plus sends emails using the SMTP account below instead of the server's default mail config.</p>
                    </div>
                </div>
            </section>

            <!-- SMTP credentials -->
            <section class="ff-settings__section" :class="{ 'ff-mail__section--disabled': !form.enabled }">
                <h3 class="ff-settings__section-title">SMTP Server</h3>

                <div class="ff-settings__row">
                    <label class="ff-settings__label">Host</label>
                    <div class="ff-settings__control">
                        <input v-model="form.host" type="text" class="ff-input" placeholder="smtp.gmail.com" :disabled="!form.enabled">
                    </div>
                </div>

                <div class="ff-settings__row">
                    <label class="ff-settings__label">Port</label>
                    <div class="ff-settings__control">
                        <input v-model="form.port" type="number" class="ff-input" placeholder="587" :disabled="!form.enabled">
                    </div>
                </div>

                <div class="ff-settings__row">
                    <label class="ff-settings__label">Encryption</label>
                    <div class="ff-settings__control">
                        <select v-model="form.encryption" class="ff-input ff-input--select" :disabled="!form.enabled">
                            <option value="tls">TLS (recommended)</option>
                            <option value="ssl">SSL</option>
                            <option value="">None</option>
                        </select>
                    </div>
                </div>

                <div class="ff-settings__row">
                    <label class="ff-settings__label">Username</label>
                    <div class="ff-settings__control">
                        <input v-model="form.username" type="text" autocomplete="off" class="ff-input" placeholder="you@gmail.com" :disabled="!form.enabled">
                    </div>
                </div>

                <div class="ff-settings__row">
                    <label class="ff-settings__label">Password</label>
                    <div class="ff-settings__control">
                        <input
                            v-model="form.password"
                            type="password"
                            autocomplete="new-password"
                            class="ff-input"
                            :placeholder="passwordSet ? '••••••••  (leave blank to keep current)' : 'App password or SMTP password'"
                            :disabled="!form.enabled"
                        >
                        <p class="ff-hint">Stored securely on the server. For Gmail, create an <strong>App Password</strong> at myaccount.google.com/apppasswords.</p>
                    </div>
                </div>
            </section>

            <!-- From address -->
            <section class="ff-settings__section" :class="{ 'ff-mail__section--disabled': !form.enabled }">
                <h3 class="ff-settings__section-title">Sender Identity</h3>

                <div class="ff-settings__row">
                    <label class="ff-settings__label">From address</label>
                    <div class="ff-settings__control">
                        <input v-model="form.from_address" type="email" class="ff-input" placeholder="you@gmail.com" :disabled="!form.enabled">
                    </div>
                </div>

                <div class="ff-settings__row">
                    <label class="ff-settings__label">From name</label>
                    <div class="ff-settings__control">
                        <input v-model="form.from_name" type="text" class="ff-input" placeholder="My Website" :disabled="!form.enabled">
                    </div>
                </div>
            </section>

            <!-- Test -->
            <section class="ff-settings__section" :class="{ 'ff-mail__section--disabled': !form.enabled }">
                <h3 class="ff-settings__section-title">Test Connection</h3>

                <div class="ff-settings__row">
                    <label class="ff-settings__label">Send test email to</label>
                    <div class="ff-settings__control">
                        <div class="ff-mail__test-row">
                            <input v-model="testEmail" type="email" class="ff-input" placeholder="you@gmail.com" :disabled="!form.enabled || testing">
                            <button
                                class="ff-btn ff-btn--ghost"
                                :disabled="!form.enabled || !testEmail || testing"
                                @click="sendTest"
                            >{{ testing ? 'Sending…' : 'Send Test' }}</button>
                        </div>
                        <p v-if="testMessage" class="ff-mail__test-msg" :class="testError ? 'ff-mail__test-msg--error' : 'ff-mail__test-msg--ok'">{{ testMessage }}</p>
                        <p class="ff-hint">Save your settings first, then send a test to confirm everything is working.</p>
                    </div>
                </div>
            </section>

            <!-- Save -->
            <div class="ff-settings__footer">
                <p v-if="saveMessage" class="ff-settings__save-msg" :class="saveError ? 'ff-settings__save-msg--error' : 'ff-settings__save-msg--ok'">{{ saveMessage }}</p>
                <button class="ff-btn ff-btn--primary" :disabled="saving" @click="save">
                    {{ saving ? 'Saving…' : 'Save Settings' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        apiUrl:  { type: String, required: true },
        saveUrl: { type: String, required: true },
        testUrl: { type: String, required: true },
    },

    data() {
        return {
            loading:     true,
            saving:      false,
            saveMessage: '',
            saveError:   false,
            testing:     false,
            testEmail:   '',
            testMessage: '',
            testError:   false,
            passwordSet: false,
            form: {
                enabled:      false,
                host:         '',
                port:         587,
                encryption:   'tls',
                username:     '',
                password:     '',
                from_address: '',
                from_name:    '',
            },
        };
    },

    async mounted() {
        try {
            const { data } = await this.$axios.get(this.apiUrl);
            this.passwordSet = data.password_set ?? false;
            const { password_set, ...rest } = data;
            this.form = { ...this.form, ...rest };
        } catch {
            //
        } finally {
            this.loading = false;
        }

        this._onSave = (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 's') {
                e.preventDefault();
                this.save();
            }
        };
        document.addEventListener('keydown', this._onSave);
    },

    beforeUnmount() {
        document.removeEventListener('keydown', this._onSave);
    },

    methods: {
        async save() {
            this.saving      = true;
            this.saveMessage = '';
            this.saveError   = false;

            try {
                const { data } = await this.$axios.post(this.saveUrl, this.form);
                if (data.success) {
                    this.saveMessage = 'Settings saved.';
                    if (this.form.password) this.passwordSet = true;
                    this.form.password = '';
                    setTimeout(() => { this.saveMessage = ''; }, 3000);
                } else {
                    this.saveMessage = data.message || 'Could not save.';
                    this.saveError   = true;
                }
            } catch (error) {
                this.saveMessage = error.response?.data?.message || 'Network error. Please try again.';
                this.saveError   = true;
            } finally {
                this.saving = false;
            }
        },

        async sendTest() {
            this.testing     = true;
            this.testMessage = '';
            this.testError   = false;

            try {
                const { data } = await this.$axios.post(this.testUrl, { to: this.testEmail });
                this.testMessage = data.message;
                this.testError   = !data.success;
            } catch (error) {
                this.testMessage = error.response?.data?.message || 'Failed to send. Check your settings and try again.';
                this.testError   = true;
            } finally {
                this.testing = false;
            }
        },
    },
};
</script>
