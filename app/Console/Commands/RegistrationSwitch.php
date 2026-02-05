<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RegistrationSwitch extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'registration:switch {state : El estado del registro (on/off)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Habilitar o deshabilitar el registro de nuevos usuarios';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $state = strtolower($this->argument('state'));

        if (!in_array($state, ['on', 'off'])) {
            $this->error('Por favor usa "on" para habilitar o "off" para deshabilitar.');
            return;
        }

        if ($state === 'off') {
            Storage::put('registration_disabled', 'true');
            $this->info('🚫 Registro de usuarios DESHABILITADO.');
            $this->comment('Nadie podrá crear cuentas nuevas hasta que lo habilites nuevamente.');
        } else {
            Storage::delete('registration_disabled');
            $this->info('✅ Registro de usuarios HABILITADO.');
            $this->comment('Los usuarios ya pueden registrarse nuevamente.');
        }
    }
}
