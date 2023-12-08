<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class SaveLoginCount extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'SaveLoginCount';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return [int]
     */
    public function handle()
    {
        $user = User::find(1);
        $user->email = $user->email . '2';
        $user->save();
        // sleep(66);
    }
}
