<?php

namespace App\Console\Commands;

use App\Domain\Identity\IdentitySync;
use Illuminate\Console\Command;

class SyncPeople extends Command
{
    protected $signature = 'school:sync-people';
    protected $description = 'Give every student a login and link users, teachers, parents and students to each other. Safe to run again.';

    public function handle(): int
    {
        $done = IdentitySync::backfill();
        $this->info("Students: {$done['students']}, teachers: {$done['teachers']}, parents: {$done['parents']}, users checked: {$done['users']}.");

        return self::SUCCESS;
    }
}
