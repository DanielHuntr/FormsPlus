<?php

namespace App\FormsPlus\Services;

use Illuminate\Support\Facades\File;

class CssBuildService
{
    public function build(): array
    {
        $root = base_path();
        $cmd  = $this->detectBuildCommand($root);

        if (! $cmd) {
            return [
                'success' => false,
                'message' => 'No build tool found. Make sure Node.js and npm are installed and node_modules exists.',
                'output'  => '',
            ];
        }

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $env     = array_merge($_ENV, ['HOME' => $root, 'PATH' => $this->nodePath()]);
        $process = proc_open("cd " . escapeshellarg($root) . " && $cmd", $descriptors, $pipes, $root, $env);

        if (! is_resource($process)) {
            return ['success' => false, 'message' => 'Could not start build process.', 'output' => ''];
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $code = proc_close($process);

        return [
            'success' => $code === 0,
            'message' => $code === 0 ? 'CSS rebuilt successfully.' : 'Build failed — check server logs.',
            'output'  => trim($stderr ?: $stdout),
        ];
    }

    private function detectBuildCommand(string $root): ?string
    {
        // Prefer local vite binary (fastest, no npm overhead)
        $vite = "$root/node_modules/.bin/vite";
        if (File::exists($vite)) {
            return escapeshellarg($vite) . ' build';
        }

        // Fall back to npm run build
        $npm = trim(shell_exec('which npm 2>/dev/null') ?? '');
        if ($npm) {
            return escapeshellarg($npm) . ' run build';
        }

        return null;
    }

    private function nodePath(): string
    {
        $existing = $_ENV['PATH'] ?? getenv('PATH') ?? '';
        $extras   = ['/usr/local/bin', '/usr/bin', '/opt/homebrew/bin'];

        return implode(':', array_unique(array_filter(array_merge(explode(':', $existing), $extras))));
    }
}
