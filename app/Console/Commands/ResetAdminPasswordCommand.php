<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetAdminPasswordCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:reset-password {password?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset admin password safely';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $password = $this->argument('password') ?? 'Shizu!@#$7656#$';

        $admin = User::where('email', 'admin@pirgachainternet.com')->first();

        if (!$admin) {
            $this->error('Admin user (admin@pirgachainternet.com) not found!');
            return 1;
        }

        $admin->password = Hash::make($password);
        $admin->status = 'active';
        $admin->save();

        if (method_exists($admin, 'assignRole')) {
            $admin->assignRole('admin');
        }

        $this->info('Admin password has been reset successfully!');
        $this->line("Email: {$admin->email}");
        $this->line("Password: {$password}");
        $this->line("Status: active");

        return 0;
    }
}
