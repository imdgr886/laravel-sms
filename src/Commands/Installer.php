<?php

namespace Imdgr886\Sms\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class Installer extends Command
{
    protected $signature = 'sms:install';

    public function handle()
    {
        $this->callSilent("vendor:publish", ['--tag' => 'sms']);
    }
}
