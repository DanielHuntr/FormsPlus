<template>
    <div class="ff-wrap">
        <header class="ff-header">
            <div class="ff-header__left">
                <h1 class="ff-title">Default Email Templates</h1>
            </div>
        </header>

        <p class="ff-email-defaults__desc">
            These templates are used for all forms unless a form has its own custom template configured.
            Use <code>{{ varExample }}</code> tokens to insert dynamic content — see the sidebar for available variables.
        </p>

        <!-- Sub-tabs -->
        <div class="ff-email-tab__subtabs" style="padding: 0 24px">
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
        <div v-show="currentSub === 'notification'">
            <div class="ff-email-tab__type-desc" style="margin: 0 24px 0">
                Sent to the admin email address configured per-form in Settings.
            </div>
            <EmailTemplateBuilder
                :api-url="emailNotificationUrl"
                :show-use-default="false"
            />
        </div>

        <!-- Confirmation -->
        <div v-show="currentSub === 'confirmation'">
            <div class="ff-email-tab__type-desc" style="margin: 0 24px 0">
                Sent to the form submitter when confirmation email is enabled in Settings.
            </div>
            <EmailTemplateBuilder
                :api-url="emailConfirmationUrl"
                :show-use-default="false"
            />
        </div>
    </div>
</template>

<script>
import EmailTemplateBuilder from './EmailTemplateBuilder.vue';

export default {
    components: { EmailTemplateBuilder },

    props: {
        emailNotificationUrl: { type: String, required: true },
        emailConfirmationUrl: { type: String, required: true },
    },

    data() {
        return {
            currentSub: 'notification',
            subtabs: [
                { key: 'notification', label: 'Notification Email' },
                { key: 'confirmation', label: 'Confirmation Email' },
            ],
        };
    },

    computed: {
        varExample() {
            return '{{variable}}';
        },
    },
};
</script>
