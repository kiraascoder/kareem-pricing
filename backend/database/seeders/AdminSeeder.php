<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('KAREEM_ADMIN_EMAIL');
        $password = env('KAREEM_ADMIN_PASSWORD');

        if (!$email || !$password || strlen($password) < 12) {
            throw new RuntimeException(
                'Isi KAREEM_ADMIN_EMAIL dan KAREEM_ADMIN_PASSWORD (minimal 12 karakter) di .env.'
            );
        }

        $user = User::where('email', $email)->first();

        if ($user) {
            if ($user->role !== 'super_admin') {
                throw new RuntimeException(
                    'Email tersebut sudah digunakan oleh akun lain.'
                );
            }

            $this->command?->info('Super admin sudah tersedia.');
            return;
        }

        User::create([
            'name' => 'Kareem Administrator',
            'email' => $email,
            'phone' => null,
            'password' => Hash::make($password),
            'role' => 'super_admin',
            'status' => 'active',
        ]);

        $this->command?->info('Super admin berhasil dibuat.');
    }
}
