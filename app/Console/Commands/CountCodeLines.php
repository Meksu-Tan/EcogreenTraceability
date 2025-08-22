<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CountCodeLines extends Command
{
    protected $signature = 'count:lines';

    protected $description = 'Count the total lines of code in the project';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $output = shell_exec('find . -name "*.php" | xargs wc -l');
        $this->info($output);
    }
}
