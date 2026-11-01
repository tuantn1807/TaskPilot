<?php

namespace ProjectsManager\Modules\Projects\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;
use ProjectsManager\Http\Controllers\Controller;
use ProjectsManager\Project;
use ProjectsManager\ProjectDocument;

class ProjectDocumentsController extends Controller
{
    public function index(Project $project): View
    {
        $project->load(['documents' => fn ($q) => $q->latest('id'), 'documents.user']);

        return view('projects.documents.index', compact('project'));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $data = $request->validate([
            'title'         => ['required', 'string', 'max:255'],
            'url'           => ['nullable', 'string', 'max:2048'],
            'document_file' => ['nullable', 'file', 'max:20480'],
            'description'   => ['nullable', 'string', 'max:1000'],
            'type'          => ['nullable', 'string', 'in:link,google_doc,notion,confluence,file'],
        ]);

        $finalUrl = trim((string) ($data['url'] ?? ''));
        $type = $data['type'] ?? 'link';

        if ($request->hasFile('document_file') && $request->file('document_file')->isValid()) {
            $file = $request->file('document_file');
            $ext = strtolower($file->getClientOriginalExtension() ?: 'dat');
            $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) ?: 'document')
                . '-' . time() . '.' . $ext;
            $uploadDir = public_path('uploads/project-documents');
            if (!is_dir($uploadDir)) {
                @mkdir($uploadDir, 0775, true);
            }
            $file->move($uploadDir, $safeName);
            $finalUrl = url('/uploads/project-documents/' . $safeName);
            $type = 'file';
        }

        if ($finalUrl === '') {
            return back()
                ->withInput()
                ->withErrors(['url' => 'Vui lòng nhập đường dẫn URL hoặc tải lên tệp tài liệu đính kèm.']);
        }

        $project->documents()->create([
            'title'       => $data['title'],
            'url'         => $finalUrl,
            'description' => $data['description'] ?? null,
            'type'        => $type,
            'user_id'     => Auth::id(),
        ]);

        return back()->with('success', 'Đã thêm tài liệu dự án thành công.');
    }

    public function destroy(Project $project, ProjectDocument $document): RedirectResponse
    {
        abort_unless((int) $document->project_id === (int) $project->id, 404);
        $document->delete();

        return back()->with('success', 'Đã xóa tài liệu dự án.');
    }
}
