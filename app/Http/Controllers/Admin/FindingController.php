<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFindingRequest;
use App\Http\Requests\UpdateFindingRequest;
use App\Models\Capture;
use App\Models\Finding;
use App\Models\FindingAttachment;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FindingController extends Controller
{
    /** Hallazgo manual del ingeniero (sección 5.6). */
    public function store(StoreFindingRequest $request, Capture $capture): RedirectResponse
    {
        $finding = Finding::create($request->validated() + [
            'capture_id' => $capture->id,
            'device_id' => $capture->device_id,
            'rule_code' => 'MANUAL',
            'is_manual' => true,
            'first_seen_capture_id' => $capture->id,
            'edited_by' => $request->user()->id,
            'file_location' => 'hallazgo manual',
        ]);

        AuditLogger::log('created', $finding, ['title' => $finding->title]);

        return back()->with('success', 'Hallazgo manual agregado.');
    }

    public function update(UpdateFindingRequest $request, Finding $finding): RedirectResponse
    {
        $original = $finding->only(['level', 'status', 'title']);

        $finding->update($request->validated() + [
            'edited_by' => $request->user()->id,
        ]);

        AuditLogger::log('updated', $finding, [
            'before' => $original,
            'after' => $finding->only(['level', 'status', 'title']),
        ]);

        return back()->with('success', 'Hallazgo actualizado.');
    }

    public function destroy(Request $request, Finding $finding): RedirectResponse
    {
        $this->authorize('delete', $finding);

        AuditLogger::log('deleted', $finding, ['title' => $finding->title, 'rule' => $finding->rule_code]);

        foreach ($finding->attachments as $attachment) {
            Storage::disk('public')->delete($attachment->path);
        }

        $finding->delete();

        return back()->with('success', 'Hallazgo eliminado del reporte.');
    }

    /** Evidencias adicionales: capturas de pantalla, fotos, logs, archivos. */
    public function storeAttachment(Request $request, Finding $finding): RedirectResponse
    {
        $this->authorize('update', $finding);

        $request->validate([
            'attachment' => ['required', 'file', 'max:10240'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $file = $request->file('attachment');
        $isImage = str_starts_with((string) $file->getMimeType(), 'image/');

        $path = $file->store("finding-attachments/{$finding->id}", 'public');

        $finding->attachments()->create([
            'type' => $isImage ? 'image' : 'file',
            'path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'caption' => $request->string('caption') ?: null,
        ]);

        return back()->with('success', 'Evidencia adjuntada.');
    }

    public function destroyAttachment(FindingAttachment $attachment): RedirectResponse
    {
        $this->authorize('update', $attachment->finding);

        Storage::disk('public')->delete($attachment->path);
        $attachment->delete();

        return back()->with('success', 'Evidencia eliminada.');
    }
}
