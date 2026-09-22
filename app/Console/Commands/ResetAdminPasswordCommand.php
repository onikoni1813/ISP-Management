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
    protected $signature = 'admin:reset-password {password?} {email?}';

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
        $email = $this->argument('email') ?? 'pirgachainternet@gmail.com';

        $admin = User::where('email', $email)
            ->orWhere('email', 'admin@pirgachainternet.com')
            ->first();

        if (!$admin) {
            $admin = User::create([
                'name' => 'Pirgacha Admin',
                'email' => $email,
                'phone' => '01711000000',
                'password' => Hash::make($password),
                'status' => 'active',
                'email_verified_at' => now(),
            ]);
        } else {
            $admin->email = $email;
            $admin->password = Hash::make($password);
            $admin->status = 'active';
            $admin->save();
        }

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
