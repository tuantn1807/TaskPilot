<?php

namespace ProjectsManager\Modules\Tasks\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use ProjectsManager\Http\Controllers\Controller;
use ProjectsManager\Task;
use ProjectsManager\TaskLink;

class TaskLinksController extends Controller
{
    public function store(Request $request, Task $task): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'target_task_id' => ['required', 'integer', 'different:task_id', 'exists:tasks,id'],
            'link_type' => ['required', 'string', 'in:' . implode(',', array_keys(TaskLink::$linkTypes))],
        ], [
            'target_task_id.different' => 'Không thể liên kết công việc với chính nó.',
            'target_task_id.exists' => 'Công việc liên kết không tồn tại.',
            'link_type.in' => 'Loại liên kết không hợp lệ.',
        ]);

        $targetTask = Task::findOrFail($validated['target_task_id']);

        if ($targetTask->id === $task->id) {
            return back()->with('error', 'Không thể liên kết công việc với chính nó.');
        }

        TaskLink::createBidirectional(
            $task->id,
            $targetTask->id,
            $validated['link_type'],
            Auth::id()
        );

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã liên kết công việc thành công.',
            ]);
        }

        return back()->with('success', 'Đã liên kết công việc thành công.');
    }

    public function destroy(Request $request, TaskLink $link): JsonResponse|RedirectResponse
    {
        $link->removeBidirectional();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã gỡ liên kết công việc.',
            ]);
        }

        return back()->with('success', 'Đã gỡ liên kết công việc.');
    }
}

