import '../css/cp.css';
import FormsPlusFieldtype from './FormsPlusFieldtype.vue';
import FormList from './FormList.vue';
import FormBuilder from './FormBuilder.vue';
import SubmissionsPanel from './SubmissionsPanel.vue';
import SettingsPanel from './SettingsPanel.vue';
import FormEmailTab from './FormEmailTab.vue';
import EmailTemplateBuilder from './EmailTemplateBuilder.vue';
import DefaultTemplatesPage from './DefaultTemplatesPage.vue';
import FormStylesTab from './FormStylesTab.vue';
import MailSettingsPanel from './MailSettingsPanel.vue';

Statamic.booting(() => {
    Statamic.$components.register('forms-plus-fieldtype', FormsPlusFieldtype);
    Statamic.$components.register('forms-plus-index', FormList);
    Statamic.$components.register('forms-plus-builder', FormBuilder);
    Statamic.$components.register('forms-plus-submissions', SubmissionsPanel);
    Statamic.$components.register('forms-plus-settings', SettingsPanel);
    Statamic.$components.register('forms-plus-email-tab', FormEmailTab);
    Statamic.$components.register('forms-plus-email-builder', EmailTemplateBuilder);
    Statamic.$components.register('forms-plus-default-templates', DefaultTemplatesPage);
    Statamic.$components.register('forms-plus-styles', FormStylesTab);
    Statamic.$components.register('forms-plus-mail-settings', MailSettingsPanel);
});
