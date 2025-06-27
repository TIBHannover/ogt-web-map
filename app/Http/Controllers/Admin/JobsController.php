<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class JobsController extends Controller
{
    /**
     * Display a listing of the queued jobs.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Get all jobs from the jobs table
        $jobs = DB::table('jobs')
            ->select('id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Parse the payload for each job to extract readable information
        foreach ($jobs as $job) {
            $payload = json_decode($job->payload);
            $job->job_name = $payload->displayName ?? 'Unknown';
            $job->command_name = isset($payload->data->commandName) ? $payload->data->commandName : null;
            
            // Extract job data if available
            if (isset($payload->data->command)) {
                $command = unserialize($payload->data->command);
                $job->job_data = is_object($command) ? json_encode($command) : null;
            } else {
                $job->job_data = null;
            }
            
            // Format timestamps
            $job->created_at = date('Y-m-d H:i:s', strtotime($job->created_at));
            $job->available_at = date('Y-m-d H:i:s', $job->available_at);
            $job->reserved_at = $job->reserved_at ? date('Y-m-d H:i:s', $job->reserved_at) : null;
        }

        // Get all failed jobs
        $failedJobs = DB::table('failed_jobs')
            ->select('id', 'uuid', 'connection', 'queue', 'payload', 'exception', 'failed_at')
            ->orderBy('failed_at', 'desc')
            ->paginate(20, ['*'], 'failed_page');

        // Parse the payload for each failed job to extract readable information
        foreach ($failedJobs as $job) {
            $payload = json_decode($job->payload);
            $job->job_name = $payload->displayName ?? 'Unknown';
            $job->command_name = isset($payload->data->commandName) ? $payload->data->commandName : null;
            
            // Extract job data if available
            if (isset($payload->data->command)) {
                try {
                    $command = unserialize($payload->data->command);
                    $job->job_data = is_object($command) ? json_encode($command) : null;
                } catch (\Exception $e) {
                    $job->job_data = 'Could not unserialize command: ' . $e->getMessage();
                }
            } else {
                $job->job_data = null;
            }
            
            // Format timestamp
            $job->failed_at = date('Y-m-d H:i:s', strtotime($job->failed_at));

            // Truncate exception for display
            $job->exception_preview = mb_substr($job->exception, 0, 200) . (mb_strlen($job->exception) > 200 ? '...' : '');
        }

        return view('admin.jobs.index', compact('jobs', 'failedJobs'));
    }

    /**
     * Delete a specific job.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            DB::table('jobs')->where('id', $id)->delete();
            return redirect()->route('admin.jobs.index')
                ->with('success', 'Job wurde erfolgreich gelöscht.');
        } catch (\Exception $e) {
            return redirect()->route('admin.jobs.index')
                ->with('error', 'Fehler beim Löschen des Jobs: ' . $e->getMessage());
        }
    }

    /**
     * Delete all jobs in the queue.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroyAll(Request $request)
    {
        try {
            $queue = $request->input('queue');
            $query = DB::table('jobs');
            
            if ($queue) {
                $query->where('queue', $queue);
            }
            
            $count = $query->count();
            $query->delete();
            
            return redirect()->route('admin.jobs.index')
                ->with('success', $count . ' Jobs wurden erfolgreich gelöscht.');
        } catch (\Exception $e) {
            return redirect()->route('admin.jobs.index')
                ->with('error', 'Fehler beim Löschen der Jobs: ' . $e->getMessage());
        }
    }

    /**
     * Delete a failed job.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroyFailed($id)
    {
        try {
            DB::table('failed_jobs')->where('id', $id)->delete();
            return redirect()->route('admin.jobs.index')
                ->with('success', 'Fehlgeschlagener Job wurde erfolgreich gelöscht.');
        } catch (\Exception $e) {
            return redirect()->route('admin.jobs.index')
                ->with('error', 'Fehler beim Löschen des fehlgeschlagenen Jobs: ' . $e->getMessage());
        }
    }

    /**
     * Delete all failed jobs.
     *
     * @return \Illuminate\Http\Response
     */
    public function destroyAllFailed(Request $request)
    {
        try {
            $queue = $request->input('failed_queue');
            $query = DB::table('failed_jobs');
            
            if ($queue) {
                $query->where('queue', $queue);
            }
            
            $count = $query->count();
            $query->delete();
            
            return redirect()->route('admin.jobs.index')
                ->with('success', $count . ' fehlgeschlagene Jobs wurden erfolgreich gelöscht.');
        } catch (\Exception $e) {
            return redirect()->route('admin.jobs.index')
                ->with('error', 'Fehler beim Löschen der fehlgeschlagenen Jobs: ' . $e->getMessage());
        }
    }

    /**
     * Retry a failed job.
     *
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function retryFailed($id)
    {
        try {
            // Get the failed job
            $failedJob = DB::table('failed_jobs')->where('id', $id)->first();
            
            if (!$failedJob) {
                return redirect()->route('admin.jobs.index')
                    ->with('error', 'Fehlgeschlagener Job nicht gefunden.');
            }

            // Use Artisan to retry the job by UUID
            Artisan::call('queue:retry', ['id' => [$failedJob->uuid]]);
            
            $output = Artisan::output();
            Log::info('Job retry attempt', ['id' => $id, 'uuid' => $failedJob->uuid, 'output' => $output]);
            
            return redirect()->route('admin.jobs.index')
                ->with('success', 'Job wurde erfolgreich neu in die Warteschlange gestellt.');
        } catch (\Exception $e) {
            Log::error('Failed to retry job', ['id' => $id, 'error' => $e->getMessage()]);
            return redirect()->route('admin.jobs.index')
                ->with('error', 'Fehler beim Neustart des Jobs: ' . $e->getMessage());
        }
    }

    /**
     * Retry all failed jobs.
     *
     * @return \Illuminate\Http\Response
     */
    public function retryAllFailed(Request $request)
    {
        try {
            $queue = $request->input('retry_queue');
            
            if ($queue) {
                // Get UUIDs of failed jobs from the specified queue
                $uuids = DB::table('failed_jobs')
                    ->where('queue', $queue)
                    ->pluck('uuid')
                    ->toArray();
            } else {
                // Get all UUIDs of failed jobs
                $uuids = DB::table('failed_jobs')
                    ->pluck('uuid')
                    ->toArray();
            }
            
            if (empty($uuids)) {
                return redirect()->route('admin.jobs.index')
                    ->with('info', 'Keine fehlgeschlagenen Jobs zum Neustarten gefunden.');
            }
            
            // Use Artisan to retry the jobs
            Artisan::call('queue:retry', ['id' => $uuids]);
            
            $output = Artisan::output();
            Log::info('Bulk job retry attempt', ['count' => count($uuids), 'output' => $output]);
            
            return redirect()->route('admin.jobs.index')
                ->with('success', count($uuids) . ' fehlgeschlagene Jobs wurden erfolgreich neu in die Warteschlange gestellt.');
        } catch (\Exception $e) {
            Log::error('Failed to retry jobs', ['error' => $e->getMessage()]);
            return redirect()->route('admin.jobs.index')
                ->with('error', 'Fehler beim Neustart der Jobs: ' . $e->getMessage());
        }
    }
}