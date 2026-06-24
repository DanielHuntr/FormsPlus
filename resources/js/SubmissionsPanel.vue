<template>
    <div class="ff-submissions">
        <!-- Loading -->
        <div v-if="loading" class="ff-submissions__loading">
            Loading submissions…
        </div>

        <!-- Error -->
        <div v-else-if="loadError" class="ff-submissions__error">
            Could not load submissions. Please refresh and try again.
        </div>

        <!-- Empty -->
        <div v-else-if="submissions.length === 0" class="ff-empty">
            <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline></svg>
            <p>No submissions yet.</p>
        </div>

        <!-- Table -->
        <div v-else class="ff-card">
            <div class="ff-submissions__toolbar">
                <p class="ff-submissions__count">{{ submissions.length }} submission{{ submissions.length === 1 ? '' : 's' }}</p>
                <a :href="exportUrl" class="ff-btn ff-btn--sm" download>
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="17 8 12 13 7 8"></polyline><line x1="12" y1="2" x2="12" y2="13"></line></svg>
                    Export CSV
                </a>
            </div>

            <div class="ff-submissions__table-wrap">
                <table class="ff-table ff-submissions__table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th v-for="col in columns" :key="col">{{ formatLabel(col) }}</th>
                            <th class="ff-submissions__th-action"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="sub in submissions" :key="sub.id">
                            <td class="ff-submissions__date">{{ sub.date }}</td>
                            <td v-for="col in columns" :key="col" class="ff-submissions__cell">
                                {{ sub.data[col] || '—' }}
                            </td>
                            <td class="ff-submissions__actions">
                                <button
                                    class="ff-btn ff-btn--sm ff-btn--danger"
                                    @click="deleteSubmission(sub)"
                                    title="Delete submission"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"></path></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        submissionsUrl: { type: String, required: true },
        exportUrl:      { type: String, required: true },
    },

    data() {
        return {
            loading:     true,
            loadError:   false,
            submissions: [],
            columns:     [],
        };
    },

    async mounted() {
        await this.load();
    },

    methods: {
        async load() {
            this.loading   = true;
            this.loadError = false;
            try {
                const { data } = await window.axios.get(this.submissionsUrl);
                this.submissions = data.submissions ?? [];
                this.columns     = data.columns ?? [];
            } catch {
                this.loadError = true;
            } finally {
                this.loading = false;
            }
        },

        async deleteSubmission(sub) {
            if (! confirm(`Delete this submission from ${sub.date}? This cannot be undone.`)) return;
            try {
                const url = this.submissionsUrl.replace(/\/submissions$/, `/submissions/${sub.id}`);
                await window.axios.delete(url);
                this.submissions = this.submissions.filter(s => s.id !== sub.id);
            } catch {
                alert('Failed to delete. Please try again.');
            }
        },

        formatLabel(handle) {
            return handle.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
        },
    },
};
</script>
