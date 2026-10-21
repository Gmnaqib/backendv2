<?php

namespace App\Console\Commands;

use App\Http\Controllers\API\UserController;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:sync-users')]
#[Description('Command description')]
class SyncUsers extends Command
{
    
    public function handle()
    {
        app(UserController::class)->syncFromApi();
    }
}
