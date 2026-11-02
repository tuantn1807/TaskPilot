<?php

namespace ProjectsManager\Modules\Projects\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use ProjectsManager\CustomField;
use ProjectsManager\Http\Controllers\Controller;
use ProjectsManager\Project;
use ProjectsManager\Task;
use ProjectsManager\TaskCustomFieldValue;

class CustomFieldsController extends Controller
{
    public function index(Project $project): View
    {
        $project->load(['customFields.creator']);

        return view('projects.custom_fields.index', [
            'project' => $project,
            'customFields' => $project->customFields,
            'availableTypes' => CustomField::$types,
        ]);
    }

    public function store(Request $request, Project $project): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'field_key' => ['nullable', 'string', 'max:50', 'alpha_dash'],
            'field_type' => ['required', 'string', 'in:' . implode(',', array_keys(CustomField::$types))],
            'options' => ['nullable', 'string'], // newline or comma-separated options
            'is_required' => ['nullable', 'boolean'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $fieldKey = $validated['field_key'] ? Str::slug($validated['field_key'], '_') : Str::slug($validated['name'], '_');

        // Ensure field_key uniqueness within project
        if (CustomField::where('project_id', $project->id)->where('field_key', $fieldKey)->exists()) {
            $fieldKey = $fieldKey . '_' . time();
        }

        $optionsArray = null;
        if ($validated['field_type'] === 'select' && ! empty($validated['options'])) {
            $optionsArray = array_values(array_filter(array_map('trim', preg_split('/[\r\n,]+/', $validated['options']))));
        }

        $field = $project->customFields()->create([
            'name' => $validated['name'],
            'field_key' => $fieldKey,
            'field_type' => $validated['field_type'],
            'options' => $optionsArray,
            'is_required' => $request->boolean('is_required'),
            'description' => $validated['description'] ?? null,
            'order_index' => $project->customFields()->count() + 1,
            'created_by' => Auth::id(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã thêm trường tùy chỉnh mới.', 'field' => $field]);
        }

        return back()->with('success', 'Đã thêm trường tùy chỉnh "' . $field->name . '" thành công.');
    }

    public function destroy(Request $request, Project $project, CustomField $customField): RedirectResponse|JsonResponse
    {
        if ($customField->project_id !== $project->id) {
            abort(403);
        }

        $customField->delete();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã xóa trường tùy chỉnh.']);
        }

        return back()->with('success', 'Đã xóa trường tùy chỉnh thành công.');
    }

    /**
     * Save custom field values for a specific task
     */
    public function saveTaskValues(Request $request, Task $task): RedirectResponse|JsonResponse
    {
        $values = $request->input('custom_fields', []);
        $project = $task->project;
        $fields = $project ? $project->customFields : collect();

        foreach ($fields as $field) {
            $fieldKey = $field->field_key;
            $val = $values[$fieldKey] ?? $values[$field->id] ?? null;

            if ($field->field_type === 'checkbox') {
                $val = ! empty($val) ? '1' : '0';
            }

            TaskCustomFieldValue::updateOrCreate(
                ['task_id' => $task->id, 'custom_field_id' => $field->id],
                ['value' => is_array($val) ? json_encode($val) : (string) $val]
            );
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Đã lưu giá trị các trường tùy chỉnh.']);
        }

        return back()->with('success', 'Đã lưu các trường tùy chỉnh của công việc thành công.');
    }
}

