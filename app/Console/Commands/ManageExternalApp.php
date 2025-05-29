<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use App\Models\ExternalApp;

class ManageExternalApp extends Command
{
    protected $signature = 'externalapp 
                            {action : add | list | edit | delete}
                            {--id= : ID of the app (required for edit/delete)}
                            {--name= : Name of the app}
                            {--expires=30 : Token expiry in days (for add/edit)}
                            {--never-expire : Token will never expire}
                            {--is_active= : Set active status (true/false)}';

    protected $description = 'Manage External Apps (add, list, edit, delete)';

    public function handle()
    {
        $action = $this->argument('action');

        match ($action) {
            'add' => $this->addApp(),
            'list' => $this->listApps(),
            'edit' => $this->editApp(),
            'delete' => $this->deleteApp(),
            default => $this->error("Invalid action. Use: add | list | edit | delete")
        };
    }

    protected function addApp()
    {
        $name = $this->option('name') ?? $this->ask('App name?');
        $clientId = (string) Str::uuid();
        $apiToken = hash('sha256', Str::random(60));

        $neverExpire = $this->option('never-expire');
        $expiry = $neverExpire ? null : now()->addDays((int) $this->option('expires'));

        $isActive = $this->option('is_active');
        $isActive = is_null($isActive) ? true : filter_var($isActive, FILTER_VALIDATE_BOOLEAN);

        $app = ExternalApp::create([
            'name' => $name,
            'client_id' => $clientId,
            'api_token' => $apiToken,
            'token_expires_at' => $expiry,
            'is_active' => $isActive
        ]);

        $this->info("App created:");
        $this->displayApp($app);
    }

    protected function listApps()
    {
        $apps = ExternalApp::all(['id', 'name', 'client_id','api_token', 'is_active', 'token_expires_at', 'created_at']);

        if ($apps->isEmpty()) {
            $this->info("Tiada aplikasi.");
            return;
        }

        $this->table(
            ['ID', 'Name', 'Client ID', 'API Token','Active', 'Expires At', 'Created'],
            $apps->map(function($app) {
                return [
                    'ID' => $app->id,
                    'Name' => $app->name,
                    'Client ID' => $app->client_id,
                    'API Token' => $app->api_token,
                    'Active' => $app->is_active ? 'YA' : 'TIDAK',
                    'Expires At' => $app->token_expires_at?->format('Y-m-d') ?? 'Tidak Tamat',
                    'Created' => $app->created_at->format('Y-m-d'),
                ];
            })->toArray()
        );
    }

    protected function editApp()
    {
        $id = $this->option('id');
        if (!$id) {
            $this->error("Sila berikan --id untuk edit.");
            return;
        }

        $app = ExternalApp::find($id);
        if (!$app) {
            $this->error("App tidak dijumpai.");
            return;
        }

        if ($name = $this->option('name')) {
            $app->name = $name;
        }

        if ($this->option('never-expire')) {
            $app->token_expires_at = null;
        } elseif ($days = (int) $this->option('expires')) {
            $app->token_expires_at = now()->addDays($days);
        }

        if (!is_null($this->option('is_active'))) {
            $app->is_active = filter_var($this->option('is_active'), FILTER_VALIDATE_BOOLEAN);
        }

        $app->save();

        $this->info("App dikemaskini:");
        $this->displayApp($app);
    }

    protected function deleteApp()
    {
        $id = $this->option('id');
        if (!$id) {
            $this->error("Sila berikan --id untuk delete.");
            return;
        }

        $app = ExternalApp::find($id);
        if (!$app) {
            $this->error("App tidak dijumpai.");
            return;
        }

        $confirmed = $this->confirm("Padam aplikasi '{$app->name}'?");

        if ($confirmed) {
            $app->delete();
            $this->info("App dipadam.");
        } else {
            $this->info("Dibatalkan.");
        }
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
