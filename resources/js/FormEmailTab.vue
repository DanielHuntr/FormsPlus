<template>
    <div class="ff-email-tab">
        <!-- Sub-tabs -->
        <div class="ff-email-tab__subtabs">
            <button
                v-for="sub in subtabs"
                :key="sub.key"
                class="ff-email-tab__subtab"
                :class="{ 'ff-email-tab__subtab--active': currentSub === sub.key }"
                @click="currentSub = sub.key"
            >
                {{ sub.label }}
            </button>
        </div>

        <!-- Notification -->
        <div v-show="currentSub === 'notification'" class="ff-email-tab__panel">
            <div class="ff-email-tab__type-desc">
                <strong>Notification email</strong> — sent to the email address configured in Settings when a form is submitted.
            </div>
            <EmailTemplateBuilder
                ref="notifBuilder"
                :api-url="emailNotificationUrl"
                :preview-api-url="emailPreviewUrl"
                :show-use-default="true"
                :standalone="standalone"
                @dirty="$emit('dirty')"
            />
        </div>

        <!-- Confirmation -->
        <div v-show="currentSub === 'confirmation'" class="ff-email-tab__panel">
            <div class="ff-email-tab__type-desc">
                <strong>Confirmation email</strong> — sent to the person who submitted the form. Configure the recipient field in Settings.
            </div>
            <EmailTemplateBuilder
                ref="confirmBuilder"
                :api-url="emailConfirmationUrl"
                :preview-api-url="emailPreviewUrl"
                :show-use-default="true"
                :standalone="standalone"
                @dirty="$emit('dirty')"
            />
        </div>
    </div>
</template>

<script>
import EmailTemplateBuilder from './EmailTemplateBuilder.vue';

export default {
    components: { EmailTemplateBuilder },

    props: {
        emailNotificationUrl:  { type: String, required: true },
        emailConfirmationUrl:  { type: String, required: true },
        emailPreviewUrl:       { type: String, required: true },
        standalone:            { type: Boolean, default: true },
    },

    data() {
        return {
            currentSub: 'notification',
            subtabs: [
                { key: 'notification',  label: 'Notification Email' },
                { key: 'confirmation',  label: 'Confirmation Email' },
            ],
        };
    },

    methods: {
        async save() {
            await Promise.all([
                this.$refs.notifBuilder?.save(),
                this.$refs.confirmBuilder?.save(),
            ]);
        },
    },
};
</script>
