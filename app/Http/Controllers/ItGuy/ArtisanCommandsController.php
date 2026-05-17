<?php

namespace App\Http\Controllers\ItGuy;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;

class ArtisanCommandsController extends Controller
{
    public function index()
    {
        return view('pages.it_guy.artisan_commands.index');
    }

    // Optimze
    public function optimize()
    {
        $command_name = 'optimize';
        $status = $this->handle_command($command_name);

        return $status;
    }

    // Optimze clear
    public function optimize_clear()
    {
        $command_name = 'optimize:clear';
        $status = $this->handle_command($command_name);

        return $status;
    }

    // Route cache
    public function route_cache()
    {
        $command_name = 'route:cache';
        $status = $this->handle_command($command_name);

        return $status;
    }

    // Route cache clear
    public function route_clear()
    {
        $command_name = 'route:clear';
        $status = $this->handle_command($command_name);

        return $status;
    }

    // Config cache
    public function config_cache()
    {
        $command_name = 'config:cache';
        $status = $this->handle_command($command_name);

        return $status;
    }

    // Config cache clear
    public function config_clear()
    {
        $command_name = 'config:clear';
        $status = $this->handle_command($command_name);

        return $status;
    }

    // Event cache
    public function event_cache()
    {
        $command_name = 'event:cache';
        $status = $this->handle_command($command_name);

        return $status;
    }

    // Event cache clear
    public function event_clear()
    {
        $command_name = 'event:clear';
        $status = $this->handle_command($command_name);

        return $status;
    }

    // View cache
    public function view_cache()
    {
        $command_name = 'view:cache';
        $status = $this->handle_command($command_name);

        return $status;
    }

    // View cache clear
    public function view_clear()
    {
        $command_name = 'view:clear';
        $status = $this->handle_command($command_name);

        return $status;
    }

    // Log viewer publish
    public function log_viewer_publish()
    {
        $command_name = 'vendor:publish --tag=log-viewer-assets --force';
        $status = $this->handle_command($command_name);

        return $status;
    }

    // Clean activity log
    public function activity_log_clean()
    {
        $command_name = 'activitylog:clean --force';
        $status = $this->handle_command($command_name);

        return $status;
    }

    // Storage link
    public function storage_link()
    {
        $command_name = 'storage:link';
        $status = $this->handle_command($command_name);

        return $status;
    }

    // Unlink the storage symlink
    public function storage_unlink()
    {
        $command_name = 'storage:unlink';
        $status = $this->handle_command($command_name);

        return $status;
    }

    /**
     * Handle the given command and return appropriate response
     * @param mixed $command_name
     * @param mixed $response_name
     * @return mixed|\Illuminate\Http\JsonResponse
     */
    private function handle_command($command_name)
    {
        // Some commands can be prohibited for some reasons; 
        // eg., for route:cache; routes with the same name cannot be cached (routes that are used in both tenant and central domains).
        $prohibited_commands_name = ['route:cache', 'optimize'];
        $response_name = str_replace(":", " ", $command_name);

        if (in_array($command_name, $prohibited_commands_name)) {
            return Qs::json("The $response_name command is prohibited.", false);
        }

        $status = Artisan::call($command_name);
        if ($status === 0) {
            return Qs::json("The $response_name command run successfully.", true);
        }

        return Qs::json("The $response_name command failed to run.", false);
    }

    public function tenants_command_runner(string $command_id, $tenant_id)
    {
        $command_name = match ($command_id) {
            '1' => 'tenants:migrate-fresh',
            '2' => 'tenants:seed',
            default => null,
        };
        $response_name = str_replace(':', ' ', $command_name);

        $tenant_id = Qs::decodeHash($tenant_id);
        try {
            $status = Artisan::call('tenants:run', [
                'commandname' => $command_name,
                '--tenants' => [$tenant_id],
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->withErrors(['command_error' => $e->getMessage()]);
        }

        if ($status === 0) {
            return back()->with('pop_success', "The $response_name command run successfully for the specified tenant.")->with('pop_timer', 0);
        }

        return back()->with('pop_error', "The $response_name command failed to run for the specified tenant.")->with('pop_timer', 0);
    }
}
