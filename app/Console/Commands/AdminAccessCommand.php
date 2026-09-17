<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AdminAccessCommand extends Command
{
    protected $signature = 'admin:access {user : Exact existing account ID} {--revoke : Remove administrator access}';

    protected $description = 'Grant or revoke an existing verified individual administrator; no shared accounts are created';

    public function handle(): int
    {
        $id = filter_var($this->argument('user'), FILTER_VALIDATE_INT);
        if (! $id) {
            $this->error('A numeric existing account ID is required.');

            return self::FAILURE;
        }

        return DB::transaction(function () use ($id) {
            $user = User::whereKey($id)->lockForUpdate()->first();
            if (! $user || (! $this->option('revoke') && (! $user->hasVerifiedEmail() || ! $user->canAuthenticate() || $user->account_type !== 'individual'))) {
                $this->error('Access requires an existing verified active individual account.');

                return self::FAILURE;
            }
            $grant = ! $this->option('revoke');
            $user->forceFill(['is_admin' => $grant, 'auth_version' => $user->auth_version + 1, 'remember_token' => null])->save();
            DB::table('admin_audits')->insert(['actor_id' => null, 'action' => $grant ? 'admin.granted' : 'admin.revoked', 'subject_type' => 'user', 'subject_id' => $id, 'changes' => json_encode(['source' => 'console', 'is_admin' => $grant], JSON_THROW_ON_ERROR)]);
            $this->info('Administrator access updated; existing sessions revoked.');

            return self::SUCCESS;
        });
    }
}
