<?php

namespace ProjectsManager\Modules\Projects\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use ProjectsManager\Http\Controllers\Controller;
use ProjectsManager\Project;
use ProjectsManager\RetroCard;
use ProjectsManager\Retrospective;
use ProjectsManager\RetroVote;
use ProjectsManager\Task;

class RetrospectivesController extends Controller
{
    public function index(Request $request, Project $project): View
    {
        $project->load(['sprints' => function ($q) {
            $q->orderByDesc('id');
        }]);

        $selectedSprintId = $request->filled('sprint_id')
            ? ($request->input('sprint_id') === 'all' ? null : (int) $request->input('sprint_id'))
            : ($project->sprints->firstWhere('status', 'active')?->id ?? $project->sprints->first()?->id ?? null);

        $sort = $request->input('sort', 'votes'); // 'votes' or 'newest'

        // Get or create retrospective for this scope
        $retrospective = Retrospective::firstOrCreate(
            [
                'project_id' => $project->id,
                'sprint_id' => $selectedSprintId,
            ],
            [
                'title' => $selectedSprintId ? 'Sprint Retrospective' : 'Project Retrospective',
                'status' => 'open',
                'created_by' => Auth::id(),
            ]
        );

        // Fetch cards
        $cardsQuery = $retrospective->cards()
            ->with(['user', 'votes', 'convertedTask'])
            ->withCount('votes');

        if ($sort === 'votes') {
            $cardsQuery->orderByDesc('votes_count')->orderByDesc('id');
        } else {
            $cardsQuery->orderByDesc('id');
        }

        $allCards = $cardsQuery->get();

        $wentWellCards = $allCards->where('column_type', 'went_well')->values();
        $toImproveCards = $allCards->where('column_type', 'to_improve')->values();
        $actionItemCards = $allCards->where('column_type', 'action_item')->values();

        $totalVotes = $allCards->sum('votes_count');
        $participantsCount = $allCards->pluck('user_id')->filter()->unique()->count();

        return view('projects.retrospectives.index', [
            'project' => $project,
            'sprints' => $project->sprints,
            'selectedSprintId' => $selectedSprintId,
            'retrospective' => $retrospective,
            'wentWellCards' => $wentWellCards,
            'toImproveCards' => $toImproveCards,
            'actionItemCards' => $actionItemCards,
            'totalCards' => $allCards->count(),
            'totalVotes' => $totalVotes,
            'participantsCount' => $participantsCount,
            'sort' => $sort,
        ]);
    }

    public function storeCard(Request $request, Project $project, Retrospective $retrospective): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'column_type' => ['required', 'string', 'in:went_well,to_improve,action_item'],
            'content' => ['required', 'string', 'max:2000'],
            'is_anonymous' => ['nullable', 'boolean'],
        ], [
            'content.required' => 'Vui lòng nhập nội dung góp ý.',
            'column_type.in' => 'Cột không hợp lệ.',
        ]);

        $card = $retrospective->cards()->create([
            'user_id' => Auth::id(),
            'column_type' => $validated['column_type'],
            'content' => $validated['content'],
            'is_anonymous' => $request->boolean('is_anonymous'),
        ]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã thêm ý kiến vào bảng Retrospective.',
                'card' => $card,
            ]);
        }

        return back()->with('success', 'Đã thêm ý kiến vào bảng Retrospective thành công.');
    }

    public function vote(Request $request, Project $project, RetroCard $card): JsonResponse|RedirectResponse
    {
        $userId = Auth::id();
        $existing = RetroVote::where('retro_card_id', $card->id)
            ->where('user_id', $userId)
            ->first();

        if ($existing) {
            $existing->delete();
            $voted = false;
        } else {
            RetroVote::create([
                'retro_card_id' => $card->id,
                'user_id' => $userId,
            ]);
            $voted = true;
        }

        $votesCount = RetroVote::where('retro_card_id', $card->id)->count();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'voted' => $voted,
                'votes_count' => $votesCount,
            ]);
        }

        return back();
    }

    public function destroyCard(Request $request, Project $project, RetroCard $card): JsonResponse|RedirectResponse
    {
        $userId = Auth::id();

        // Permission: Only author, project creator or admin can delete
        if ($card->user_id !== $userId && $project->user_id !== $userId) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Bạn không có quyền xóa ý kiến này.'], 403);
            }
            return back()->with('error', 'Bạn không có quyền xóa ý kiến này.');
        }

        $card->delete();

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã xóa ý kiến.',
            ]);
        }

        return back()->with('success', 'Đã xóa ý kiến thành công.');
    }

    public function convertToTask(Request $request, Project $project, RetroCard $card): JsonResponse|RedirectResponse
    {
        if ($card->converted_task_id) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Ý kiến này đã được chuyển thành Task.'], 422);
            }
            return back()->with('info', 'Ý kiến này đã được chuyển thành Task.');
        }

        $activeStatuses = $project->getActiveStatuses();
        $defaultStatus = 'todo';
        if (!empty($activeStatuses)) {
            $initialStatus = collect($activeStatuses)->firstWhere('is_initial', true);
            $defaultStatus = $initialStatus['key'] ?? $activeStatuses[0]['key'] ?? 'todo';
        }

        $taskName = mb_substr(strip_tags($card->content), 0, 95);
        if (mb_strlen(strip_tags($card->content)) > 95) {
            $taskName .= '...';
        }

        $task = Task::create([
            'name' => '[Action Item] ' . $taskName,
            'description' => "<p><strong>Nguồn gốc từ Scrum Retrospective:</strong></p><blockquote>" . e($card->content) . "</blockquote>",
            'project_id' => $project->id,
            'user_id' => Auth::id(),
            'company_id' => $project->company_id,
            'status' => $defaultStatus,
            'priority' => 'medium',
            'issue_type' => 'task',
            'sprint_id' => $card->retrospective->sprint_id,
        ]);

        $task->update([
            'issue_key' => 'TP-' . str_pad((string) $task->id, 5, '0', STR_PAD_LEFT),
        ]);

        $card->update(['converted_task_id' => $task->id]);

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Đã tạo Task từ Action Item thành công.',
                'task' => $task,
            ]);
        }

        return back()->with('success', 'Đã tạo Task "' . $task->issue_key . '" từ Action Item thành công!');
    }
}

