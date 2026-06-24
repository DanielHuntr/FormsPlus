# Forms Plus

A drag-and-drop form builder addon for [Statamic](https://statamic.com). Build and manage forms directly from the Control Panel, style them with a live theme editor, and handle submissions with built-in email notifications.

---

## Requirements

- PHP 8.3+
- Statamic 6.0+

---

## Installation

Install via Composer:

```bash
composer require danielhuntr/forms-plus
```

Statamic will automatically discover and register the addon. On the first page load after installation a **Contact Us** demo form is created so you have a working example to reference.

---

## Control Panel

Navigate to **Forms Plus** in the CP sidebar. The section has three areas:

| Area | Purpose |
|---|---|
| All Forms | Create, edit, and delete forms |
| Email Templates | Set global default email templates |
| Theme | Style all forms with a live preview editor |

---

## Creating a Form

1. Go to **Forms Plus → All Forms** and click **New Form**.
2. Enter a **Title** (e.g. `Contact Us`) and a **Handle** (e.g. `contact_us`). The handle is used in your Antlers templates.
3. Click **Create Form** to open the form builder.

---

## Form Builder

The builder has four tabs:

### Fields

Drag fields from the left palette onto the canvas, or click them to add in order. Click any field on the canvas to open its settings panel on the right.

**Available field types:**

| Type | Description |
|---|---|
| Text | Single-line text input |
| Email | Email address input |
| Phone | Telephone number input |
| Number | Numeric input |
| URL | Website address input |
| Date | Native date picker |
| Time | Native time picker |
| Date & Time | Combined date and time picker |
| Textarea | Multi-line text area |
| Select | Dropdown with custom options |
| Radio | Single-choice radio group |
| Checkboxes | Multi-choice checkbox group |
| File Upload | Single or multiple file upload |

**Field settings** (shown in the right panel when a field is selected):

- **Label** — displayed above the field
- **Handle** — used as the field name in submissions
- **Instructions** — optional help text shown below the label
- **Placeholder** — ghost text inside the input (where applicable)
- **Width** — 25%, 33%, 50%, 75%, or 100% of the form row
- **Required** — toggles validation
- Type-specific options: rows (textarea), options (select/radio/checkboxes), multiple (select/file), allowed extensions (file), character limit (text/textarea)

Press **Cmd+S** (Mac) or **Ctrl+S** (Windows/Linux) or click **Save Form** to save your fields.

### Submissions

View all submissions in a table. Each row shows the submitted values, date, and a delete button. Use **Export CSV** (top right) to download all submissions as a spreadsheet.

### Settings

Configure per-form behaviour:

- **Submit button label** — override the default "Submit" text
- **On submit** — show a success message, or redirect to a URL
- **Success title / message** — shown after a successful submission
- **Enable/disable** — temporarily disable the form without deleting it

### Email

Configure email notifications per form:

- **Notification email** — sent to your team when a submission arrives. Supports custom subject, reply-to, and a drag-and-drop email body builder with variables like `{{name}}`, `{{email}}`, etc.
- **Confirmation email** — sent to the submitter. Can use the same variables to personalise the message.

Both templates can inherit from the global defaults set in **Email Templates**.

---

## Rendering a Form (Antlers)

Use the `{{ forms_plus }}` tag in any Antlers template:

```antlers
{{ forms_plus handle="contact_us" }}
```

### Parameters

| Parameter | Required | Description |
|---|---|---|
| `handle` | Yes | The form's handle |
| `redirect` | No | URL to redirect to after successful submission. Overrides the per-form setting. |
| `error_redirect` | No | URL to redirect to if validation fails |
| `submit_label` | No | Override the submit button text |

### Example with parameters

```antlers
{{ forms_plus
    handle="contact_us"
    redirect="/thank-you"
    error_redirect="/contact"
    submit_label="Send Message"
}}
```

### Success message (no redirect)

When **On submit** is set to "Show message", the page reloads with `?fpsubmitted=handle` in the URL and the form is replaced with the success message automatically. No extra template work needed.

---

## Styling Forms

### Theme editor

Go to **Forms Plus → Theme** for a live preview style editor. Choose a preset theme (Default, Minimal, Rounded, Dark) and fine-tune individual elements using Tailwind CSS utility classes. Changes are previewed in real time.

Available style targets:

| Key | Element |
|---|---|
| Form wrapper | The `<form>` element |
| Field wrapper | Each field's wrapper `<div>` |
| Label | Field labels |
| Inputs | Text, email, number, date, time, URL inputs, textareas, and selects |
| Textarea | Textarea-specific overrides |
| Select | Select-specific overrides |
| Checkbox input | Checkbox `<input>` elements |
| Radio input | Radio `<input>` elements |
| Choice label | Checkbox/radio label wrappers |
| Submit button | The submit `<button>` |
| Error | Validation error messages |
| Help text | Instruction text below labels |

### Custom CSS

The **Custom CSS** section in the theme editor accepts any CSS targeting the `.flexible-form` namespace. It is injected as a `<style>` block on the page. Click any class chip in the reference panel to insert a ready-made rule at your cursor.

**Full class reference:**

| Class | Element |
|---|---|
| `.flexible-form` | The `<form>` element |
| `.flexible-form__field` | Each field wrapper |
| `.flexible-form__label` | Field labels |
| `.flexible-form__instructions` | Help/hint text below labels |
| `.flexible-form__input` | Text inputs, textareas, and selects |
| `.flexible-form__textarea` | Textarea modifier |
| `.flexible-form__select` | Select modifier |
| `.flexible-form__checkbox` | Checkbox inputs |
| `.flexible-form__radio` | Radio inputs |
| `.flexible-form__check-label` | Checkbox/radio label wrappers |
| `.flexible-form__check-group` | Container for checkbox/radio options |
| `.flexible-form__fieldset` | Fieldset for checkbox/radio groups |
| `.flexible-form__error-msg` | Validation error messages |
| `.flexible-form__input--error` | Input modifier when a field has a validation error |
| `.flexible-form__button` | Submit button |
| `.flexible-form__submit` | Submit button wrapper |

### Field widths

Fields support fractional widths (25%, 33%, 50%, 75%, 100%). To make multiple fields sit on the same row, add this CSS to your layout — the form uses a CSS custom property per field:

```css
.flexible-form__fields {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
}

.flexible-form__field {
    width: var(--field-width, 100%);
}
```

---

## Updating

After pulling a new release, run:

```bash
composer update danielhuntr/forms-plus
php artisan optimize:clear
```

---

## Keyboard Shortcuts

| Shortcut | Action |
|---|---|
| Cmd+S / Ctrl+S | Save the current editor (fields, settings, styles, or email template) |

---

## License

MIT
