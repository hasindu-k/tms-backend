<?php

namespace App\Services;

use App\Models\Task;
use App\Models\TimeLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Exception;

class TimeLogService
{

    public function logTime($data, $task): array
    {
        $response = [];
        $status = 200;

        try {
            $data['user_id'] = Auth::id();
            $data['task_id'] = $task->id;

            $timeLog = TimeLog::create($data);

            $response = [
                'data' => $timeLog,
                'message' => 'Time log created successfully.',
            ];
        } catch (ValidationException $e) {
            $response = [
                'message' => 'Invalid data provided.',
                'errors' => $e->errors(),
            ];
            $status = 422;
        } catch (Exception $e) {
            $response = [
                'message' => 'An unexpected error occurred.',
                'error' => $e->getMessage(),
            ];
            $status = 400;
        }

        return ['response' => $response, 'status' => $status];
    }
}
