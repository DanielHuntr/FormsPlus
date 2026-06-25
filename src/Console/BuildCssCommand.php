<?php

namespace App\FormsPlus\Console;

use App\FormsPlus\Services\CssBuildService;
use Illuminate\Console\Command;

class BuildCssCommand extends Command
{
    protected $signature   = 'forms-plus:build-css';
    protected $description = 'Compile forms-plus.css through your Vite/npm build pipeline';

    public function handle(CssBuildService $builder): int
    {
        $this->info('Building CSS…');

        $result = $builder->build();

        if ($result['success']) {
            $this->info('✓ ' . $result['message']);
            return self::SUCCESS;
        }

        $this->error('✗ ' . $result['message']);
        if (! empty($result['output'])) {
            $this->line($result['output']);
        }

        return self::FAILURE;
    }
}
