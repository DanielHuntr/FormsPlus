<template>
    <div>
        <div v-if="loading" class="text-sm text-grey-60">Loading forms…</div>
        <select
            v-else
            :value="value"
            @change="$emit('input', $event.target.value || null)"
            class="input-text"
        >
            <option value="">— Select a form —</option>
            <option v-for="form in forms" :key="form.handle" :value="form.handle">
                {{ form.label }}
            </option>
        </select>
    </div>
</template>

<script>
export default {
    props: {
        value: { default: null },
        meta: { default: () => ({}) },
        config: { type: Object, default: () => ({}) },
        handle: { type: String, default: '' },
        readOnly: { type: Boolean, default: false },
        fieldtype: { type: String, default: '' },
        namePrefix: { type: String, default: '' },
        displayPrefix: { type: String, default: '' },
    },
    emits: ['input'],
    data() {
        return { forms: [], loading: true };
    },
    async mounted() {
        try {
            const url = typeof this.cp_url === 'function'
                ? this.cp_url('forms-plus/list')
                : '/cp/forms-plus/list';
            const { data } = await window.axios.get(url);
            this.forms = data;
        } catch {
            //
        } finally {
            this.loading = false;
        }
    },
};
</script>
