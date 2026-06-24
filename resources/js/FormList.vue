<template>
    <div class="ff-wrap">
        <header class="ff-header">
            <div>
                <h1 class="ff-title">Flexible Forms</h1>
                <p class="ff-subtitle">Build and manage your forms with a visual editor.</p>
            </div>
            <button class="ff-btn ff-btn--primary" @click="showCreate = true">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                New Form
            </button>
        </header>

        <!-- Empty state -->
        <div v-if="forms.length === 0 && !showCreate" class="ff-empty">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line><polyline points="10 9 9 9 8 9"></polyline></svg>
            <p>No forms yet.</p>
            <button class="ff-btn ff-btn--primary" @click="showCreate = true">Create your first form</button>
        </div>

        <!-- Forms list -->
        <div v-else-if="forms.length > 0" class="ff-card">
            <table class="ff-table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Handle</th>
                        <th>Submissions</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="form in forms" :key="form.handle">
                        <td class="ff-table__title">{{ form.title }}</td>
                        <td><code class="ff-code">{{ form.handle }}</code></td>
                        <td>{{ form.submissions }}</td>
                        <td class="ff-table__actions">
                            <a :href="form.edit_url" class="ff-btn ff-btn--sm">Edit</a>
                            <button class="ff-btn ff-btn--sm ff-btn--danger" @click="deleteForm(form)">Delete</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Create modal -->
        <div v-if="showCreate" class="ff-modal-overlay" @click.self="closeCreate">
            <div class="ff-modal">
                <div class="ff-modal__header">
                    <h2 class="ff-modal__title">New Form</h2>
                    <button class="ff-modal__close" @click="closeCreate" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
                <form @submit.prevent="createForm" class="ff-modal__body">
                    <div class="ff-field">
                        <label class="ff-label">Title <span class="ff-required">*</span></label>
                        <input
                            v-model="newTitle"
                            @input="syncHandle"
                            class="ff-input"
                            type="text"
                            placeholder="Contact Us"
                            required
                            autofocus
                        >
                    </div>
                    <div class="ff-field">
                        <label class="ff-label">Handle <span class="ff-required">*</span></label>
                        <input
                            v-model="newHandle"
                            class="ff-input ff-input--mono"
                            type="text"
                            placeholder="contact_us"
                            required
                        >
                        <p class="ff-hint">Lowercase letters, numbers, and underscores only.</p>
                    </div>
                    <p v-if="createError" class="ff-error">{{ createError }}</p>
                    <div class="ff-modal__footer">
                        <button type="button" class="ff-btn ff-btn--ghost" @click="closeCreate">Cancel</button>
                        <button type="submit" class="ff-btn ff-btn--primary" :disabled="creating">
                            {{ creating ? 'Creating…' : 'Create Form' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        initialForms: { type: String, default: '[]' },
        storeUrl: { type: String, required: true },
    },

    data() {
        return {
            forms: JSON.parse(this.initialForms || '[]'),
            showCreate: false,
            newTitle: '',
            newHandle: '',
            creating: false,
            createError: '',
        };
    },

    methods: {
        syncHandle() {
            this.newHandle = this.newTitle
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '_')
                .replace(/^_+|_+$/g, '');
        },

        closeCreate() {
            this.showCreate = false;
            this.newTitle = '';
            this.newHandle = '';
            this.createError = '';
        },

        async createForm() {
            this.creating = true;
            this.createError = '';
            try {
                const { data } = await window.axios.post(this.storeUrl, {
                    title: this.newTitle,
                    handle: this.newHandle,
                });
                if (data.redirect) {
                    window.location.href = data.redirect;
                }
            } catch (error) {
                this.createError = error.response?.data?.error || 'Something went wrong.';
            } finally {
                this.creating = false;
            }
        },

        async deleteForm(form) {
            if (!confirm(`Delete "${form.title}"? This cannot be undone.`)) return;
            try {
                await window.axios.delete(form.delete_url);
                this.forms = this.forms.filter(f => f.handle !== form.handle);
            } catch {
                alert('Failed to delete form. Please try again.');
            }
        },
    },
};
</script>
