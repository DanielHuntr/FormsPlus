<?php

namespace App\FormsPlus;

class TemplateRenderer
{
    private array $data = [];
    private array $vars = [];

    /**
     * Render a template (array of blocks) to a complete HTML email string.
     *
     * @param  array  $template        Template from TemplateManager::get()
     * @param  array  $submissionData  Raw form submission data
     * @param  array  $vars            Variable replacements: ['form_title' => '…', …]
     */
    public function render(array $template, array $submissionData = [], array $vars = []): string
    {
        $this->data = $submissionData;
        $this->vars = $vars;

        $rows = '';
        foreach ($template['blocks'] ?? [] as $block) {
            $rows .= $this->block($block);
        }

        return $this->wrap($rows);
    }

    public function interpolateSubject(string $subject): string
    {
        return $this->v($subject);
    }

    // ── Block rendering ──────────────────────────────────────────────────────

    private function block(array $b): string
    {
        return match ($b['type'] ?? '') {
            'logo'            => $this->logo($b),
            'heading'         => $this->heading($b),
            'text'            => $this->text($b),
            'button'          => $this->button($b),
            'divider'         => $this->divider($b),
            'submission_data' => $this->submissionData($b),
            'spacer'          => $this->spacer($b),
            'footer'          => $this->footer($b),
            default           => '',
        };
    }

    private function logo(array $b): string
    {
        $src = $this->v(e($b['image_url'] ?? ''));
        if (! $src) {
            return '';
        }
        $alt   = e($b['alt'] ?? '');
        $w     = max(40, min(560, (int) ($b['width'] ?? 160)));
        $align = $this->al($b, 'left');
        $link  = $this->v(e($b['link_url'] ?? ''));
        $img   = "<img src=\"{$src}\" alt=\"{$alt}\" width=\"{$w}\" style=\"display:block;max-width:100%;height:auto;border:0\">";
        $inner = $link ? "<a href=\"{$link}\" style=\"display:block\">{$img}</a>" : $img;

        return "<tr><td style=\"padding:0 0 24px;text-align:{$align}\">{$inner}</td></tr>\n";
    }

    private function heading(array $b): string
    {
        $level  = in_array($b['level'] ?? 'h2', ['h1', 'h2', 'h3']) ? ($b['level'] ?? 'h2') : 'h2';
        $sizes  = ['h1' => '28px', 'h2' => '22px', 'h3' => '18px'];
        $weight = $level === 'h3' ? '600' : '700';
        $color  = e($b['color'] ?? '#18181b');
        $align  = $this->al($b);
        $text   = $this->v(e($b['text'] ?? ''));

        return "<tr><td style=\"padding:0 0 14px\"><{$level} style=\"margin:0;font-size:{$sizes[$level]};font-weight:{$weight};line-height:1.3;color:{$color};text-align:{$align};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif\">{$text}</{$level}></td></tr>\n";
    }

    private function text(array $b): string
    {
        $color   = e($b['color'] ?? '#374151');
        $align   = $this->al($b);
        $content = nl2br($this->v(e($b['content'] ?? '')));

        return "<tr><td style=\"padding:0 0 16px;font-size:15px;line-height:1.65;color:{$color};text-align:{$align};font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif\">{$content}</td></tr>\n";
    }

    private function button(array $b): string
    {
        $label = $this->v(e($b['label'] ?? 'Click here'));
        $url   = $this->v(e($b['url'] ?? '#'));
        $align = $this->al($b, 'center');
        $bg    = e($b['bg_color'] ?? '#3b82f6');
        $fg    = e($b['text_color'] ?? '#ffffff');

        return "<tr><td style=\"padding:4px 0 20px;text-align:{$align}\"><a href=\"{$url}\" style=\"display:inline-block;padding:12px 28px;background:{$bg};color:{$fg};text-decoration:none;border-radius:6px;font-size:15px;font-weight:600;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif\">{$label}</a></td></tr>\n";
    }

    private function divider(array $b): string
    {
        $color = e($b['color'] ?? '#e4e4e7');

        return "<tr><td style=\"padding:8px 0 24px\"><div style=\"border-top:1px solid {$color}\"></div></td></tr>\n";
    }

    private function submissionData(array $b): string
    {
        if (empty($this->data)) {
            return "<tr><td style=\"padding:0 0 16px;font-size:13px;color:#9ca3af;font-style:italic\">[Form data appears here]</td></tr>\n";
        }

        $rows = '';
        foreach ($this->data as $key => $val) {
            $label  = ucwords(str_replace('_', ' ', $key));
            $value  = is_array($val) ? implode(', ', $val) : e((string) $val);
            $rows  .= "<tr>
                <td style=\"padding:9px 14px;font-size:12px;font-weight:700;color:#71717a;background:#fafafa;width:150px;border-bottom:1px solid #f0f0f0;vertical-align:top;white-space:nowrap\">{$label}</td>
                <td style=\"padding:9px 14px;font-size:14px;color:#18181b;border-bottom:1px solid #f0f0f0;vertical-align:top;word-break:break-word\">{$value}</td>
            </tr>";
        }

        $title = '';
        if ($b['show_title'] ?? true) {
            $t     = e($b['title'] ?? 'Submission Details');
            $title = "<p style=\"margin:0 0 10px;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.07em;color:#71717a\">{$t}</p>";
        }

        return "<tr><td style=\"padding:0 0 20px\">{$title}<table role=\"presentation\" width=\"100%\" cellpadding=\"0\" cellspacing=\"0\" style=\"border-collapse:collapse;border:1px solid #e4e4e7;border-radius:6px;overflow:hidden\">{$rows}</table></td></tr>\n";
    }

    private function spacer(array $b): string
    {
        $h = max(4, min(120, (int) ($b['height'] ?? 24)));

        return "<tr><td style=\"height:{$h}px;line-height:{$h}px;font-size:{$h}px\">&nbsp;</td></tr>\n";
    }

    private function footer(array $b): string
    {
        $color   = e($b['color'] ?? '#a1a1aa');
        $align   = $this->al($b, 'center');
        $content = nl2br($this->v(e($b['content'] ?? '')));

        return "<tr><td style=\"padding:20px 0 0;font-size:12px;line-height:1.6;color:{$color};text-align:{$align};border-top:1px solid #f4f4f5;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif\">{$content}</td></tr>\n";
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    /** Replace {{var}} tokens in a string. */
    private function v(string $text): string
    {
        foreach ($this->vars as $key => $val) {
            $text = str_replace('{{' . $key . '}}', $val, $text);
            $text = str_replace('{{ ' . $key . ' }}', $val, $text);
        }

        return $text;
    }

    /** Safe alignment value. */
    private function al(array $b, string $default = 'left'): string
    {
        return in_array($b['align'] ?? $default, ['left', 'center', 'right'])
            ? ($b['align'] ?? $default)
            : $default;
    }

    /** Wrap rendered rows in a full HTML email document. */
    private function wrap(string $rows): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
</head>
<body style="margin:0;padding:0;background-color:#f4f4f5">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f4f5">
<tr><td style="padding:40px 16px">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" align="center" style="max-width:600px;width:100%;margin:0 auto;background-color:#ffffff;border-radius:10px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,.06)">
<tr><td style="padding:36px 40px 28px">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
{$rows}
</table>
</td></tr>
</table>
</td></tr>
</table>
</body>
</html>
HTML;
    }
}
