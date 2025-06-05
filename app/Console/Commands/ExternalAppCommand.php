<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\ExternalApp;

class ExternalAppCommand extends Command
{
    protected $signature = 'externalapp
        {--action= : Action (list, add, show, edit, delete)}
        {--id= : ID untuk show/edit/delete}
        {--name= : Nama aplikasi}
        {--expires=30 : Tempoh luput token dalam hari (default 30)}
        {--never-expire : Jika diset, token tidak akan tamat tempoh}
        {--is_active=true : true/false untuk status aktif}';

    protected $description = 'Urus aplikasi external (add, list, show, edit, delete)';

    public function handle()
    {
        $action = $this->option('action');

        if (!$action) {
            return $this->help();
        }

        return match ($action) {
            'list'   => $this->listApps(),
            'add'    => $this->addApp(),
            'show'   => $this->showApp(),
            'edit'   => $this->editApp(),
            'delete' => $this->deleteApp(),
            default  => $this->help(),
        };
    }

    protected function help()
    {
        $this->info("Cara guna:");
        $this->line(" php artisan externalapp --action=list");
        $this->line(" php artisan externalapp --action=add --name=\"App A\" --expires=30 --is_active=true");
        $this->line(" php artisan externalapp --action=show --id=1");
        $this->line(" php artisan externalapp --action=edit --id=1 --name=\"App Baru\" --never-expire --is_active=false");
        $this->line(" php artisan externalapp --action=delete --id=1");
        $this->newLine();
        $this->line("Parameter tambahan:");
        $this->line(" --never-expire        : Token tidak akan tamat tempoh");
        $this->line(" --expires=[n]         : Tempoh luput token dalam hari (default 30)");
        $this->line(" --is_active=true|false: Status aktif aplikasi");
        return Command::SUCCESS;
    }

    protected function listApps()
    {
        $apps = ExternalApp::all(['id', 'name', 'client_id', 'api_token', 'is_active', 'token_expires_at', 'created_at']);

        if ($apps->isEmpty()) {
            $this->info("Tiada aplikasi.");
            return Command::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Active', 'Expires At', 'Created'],
            $apps->map(fn($app) => [
                $app->id,
                $app->name,
                //$app->client_id,
                //$app->api_token,
                $app->is_active ? 'YA' : 'TIDAK',
                $app->token_expires_at?->format('Y-m-d') ?? 'Tidak Tamat',
                $app->created_at->format('Y-m-d'),
            ])->toArray()
        );

        return Command::SUCCESS;
    }

    protected function addApp()
    {
        $name = $this->option('name') ?? $this->ask('Nama aplikasi?');
        $clientId = (string) Str::uuid();
        $apiToken = hash('sha256', Str::random(60));
        $neverExpire = $this->option('never-expire');
        $expiry = $neverExpire ? null : now()->addDays((int) $this->option('expires'));
        $isActive = filter_var($this->option('is_active'), FILTER_VALIDATE_BOOLEAN);

        $app = ExternalApp::create([
            'name' => $name,
            'client_id' => $clientId,
            'api_token' => $apiToken,
            'token_expires_at' => $expiry,
            'is_active' => $isActive,
        ]);

        $this->info("Aplikasi ditambah:");
        $this->displayApp($app);

        return Command::SUCCESS;
    }

    protected function showApp()
    {
        $id = $this->option('id');
        if (!$id) return $this->error("Sila berikan --id untuk paparan.");

        $app = ExternalApp::find($id);
        if (!$app) return $this->error("Aplikasi tidak dijumpai.");

        $this->displayApp($app);
        return Command::SUCCESS;
    }

    protected function editApp()
    {
        $id = $this->option('id');
        if (!$id) return $this->error("Sila berikan --id untuk kemaskini.");

        $app = ExternalApp::find($id);
        if (!$app) return $this->error("Aplikasi tidak dijumpai.");

        if ($this->option('name')) {
            $app->name = $this->option('name');
        }

        if ($this->option('never-expire')) {
            $app->token_expires_at = null;
        } elseif ($this->option('expires')) {
            $app->token_expires_at = now()->addDays((int) $this->option('expires'));
        }

        if (!is_null($this->option('is_active'))) {
            $app->is_active = filter_var($this->option('is_active'), FILTER_VALIDATE_BOOLEAN);
        }

        $app->save();

        $this->info("Aplikasi dikemaskini:");
        $this->displayApp($app);
        return Command::SUCCESS;
    }

    protected function deleteApp()
    {
        $id = $this->option('id');
        if (!$id) return $this->error("Sila berikan --id untuk padam.");

        $app = ExternalApp::find($id);
        if (!$app) return $this->error("Aplikasi tidak dijumpai.");

        if ($this->confirm("Padam aplikasi '{$app->name}'?")) {
            $app->delete();
            $this->info("Aplikasi telah dipadam.");
        } else {
            $this->info("Tindakan dibatalkan.");
        }

        return Command::SUCCESS;
    }

    protected function displayApp($app)
    {
        $this->line("ID          : {$app->id}");
        $this->line("Name        : {$app->name}");
        $this->line("Client ID   : {$app->client_id}");
        $this->line("API Token   : {$app->api_token}");
        $this->line("Expires At  : " . ($app->token_expires_at?->format('Y-m-d') ?? 'Tidak Tamat'));
        $this->line("Status Aktif: " . ($app->is_active ? 'YA' : 'TIDAK'));
    }
}
