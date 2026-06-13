<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BotTemplate;
use App\Services\AuditLogService;
use App\Services\TemplateZipImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TemplateController extends Controller
{
    public function __construct(
        private readonly TemplateZipImportService $zipImporter,
        private readonly AuditLogService $audit,
    ) {}

    public function index(): View
    {
        return view('admin.templates.index', [
            'templates' => BotTemplate::query()->withCount('commands')->latest()->paginate(25),
        ]);
    }

    public function create(): View
    {
        return view('admin.templates.form', [
            'template'        => new BotTemplate(),
            'adminCategories' => $this->templateCategories(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatedTemplate($request);
        $parsedZip = $this->zipImporter->parse($request->file('template_zip'));
        $imagePath = $this->storeImage($request);
        $zipPath = $this->zipImporter->storeTemplateFile($request->file('template_zip'));

        $template = DB::transaction(function () use ($request, $data, $imagePath, $zipPath, $parsedZip): BotTemplate {
            $template = BotTemplate::create([
                ...$data,
                'slug'              => $this->uniqueSlug($data['name']),
                'short_description' => $this->shortDescFrom($data),
                'price'             => ($data['access_type'] ?? 'free') === 'free' ? 0 : $data['price'],
                'is_featured'       => $request->boolean('is_featured') || ($data['marketplace_status'] ?? '') === 'featured',
                'created_by'        => $request->user()->id,
                'thumbnail_path'    => $imagePath,
                'template_zip_path' => $zipPath,
                'published_at'      => ($data['status'] ?? 'draft') === 'published' ? now() : null,
            ]);

            $this->zipImporter->replaceTemplateCommands($template, $parsedZip);

            return $template;
        });

        $this->audit->log('template', 'template.created', 'Template created.', [
            'template_id' => $template->id,
            'name' => $template->name,
            'status' => $template->status,
        ], $request->user(), 'success', $template);

        return redirect()->route('admin.templates.edit', $template)->with('status', 'Template created.');
    }

    public function edit(BotTemplate $template): View
    {
        $template->load('commands');

        return view('admin.templates.form', [
            'template'        => $template,
            'adminCategories' => $this->templateCategories(),
        ]);
    }

    public function update(Request $request, BotTemplate $template): RedirectResponse
    {
        $data = $this->validatedTemplate($request, $template);
        $parsedZip = $request->hasFile('template_zip') ? $this->zipImporter->parse($request->file('template_zip')) : null;
        $imagePath = $this->storeImage($request, $template);
        $zipPath = $request->hasFile('template_zip') ? $this->zipImporter->storeTemplateFile($request->file('template_zip'), $template->template_zip_path) : null;
        $status = $data['status'] ?? $template->status;

        DB::transaction(function () use ($request, $template, $data, $imagePath, $zipPath, $status, $parsedZip): void {
            $template->update([
                ...$data,
                'slug'              => $this->uniqueSlug($data['name'], $template->id),
                'short_description' => $this->shortDescFrom($data) ?: $template->short_description,
                'price'             => ($data['access_type'] ?? 'free') === 'free' ? 0 : $data['price'],
                'is_featured'       => $request->boolean('is_featured') || ($data['marketplace_status'] ?? '') === 'featured',
                'thumbnail_path'    => $imagePath ?: $template->thumbnail_path,
                'template_zip_path' => $zipPath ?: $template->template_zip_path,
                'published_at'      => $status === 'published'
                    ? ($template->published_at ?: now())
                    : $template->published_at,
            ]);

            if ($parsedZip) {
                $this->zipImporter->replaceTemplateCommands($template, $parsedZip);
            }
        });

        $this->audit->log('template', 'template.updated', 'Template updated.', [
            'template_id' => $template->id,
            'name' => $template->name,
            'status' => $template->status,
        ], $request->user(), 'success', $template);

        return back()->with('status', 'Template updated.');
    }

    public function destroy(Request $request, BotTemplate $template): RedirectResponse
    {
        $templateId = $template->id;
        $name = $template->name;
        $template->delete();

        $this->audit->log('template', 'template.deleted', 'Template deleted.', [
            'template_id' => $templateId,
            'name' => $name,
        ], $request->user(), 'warning', BotTemplate::class, $templateId);

        return redirect()->route('admin.templates.index')->with('status', 'Template deleted.');
    }

    public function publish(Request $request, BotTemplate $template): RedirectResponse
    {
        if (! $this->isPublishable($template)) {
            return back()->withErrors(['template' => 'Template needs an image, description, valid price, and at least one command before publishing.']);
        }

        $template->update(['status' => 'published', 'published_at' => $template->published_at ?: now()]);

        $this->audit->log('template', 'template.published', 'Template published.', [
            'template_id' => $template->id,
            'name' => $template->name,
        ], $request->user(), 'success', $template);

        return back()->with('status', 'Template published.');
    }

    public function archive(Request $request, BotTemplate $template): RedirectResponse
    {
        $template->update(['status' => 'archived']);

        $this->audit->log('template', 'template.unpublished', 'Template archived.', [
            'template_id' => $template->id,
            'name' => $template->name,
        ], $request->user(), 'warning', $template);

        return back()->with('status', 'Template archived.');
    }

    private function validatedTemplate(Request $request, ?BotTemplate $template = null): array
    {
        $request->merge([
            'access_type' => $request->input('access_type', 'free'),
            'price' => $request->input('price', 0),
            'currency' => $request->input('currency', 'USD'),
            'marketplace_status' => $request->input('marketplace_status', 'unlisted'),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:3000'],
            'category' => ['nullable', 'string', 'max:100', Rule::in(array_merge([''], array_keys($this->templateCategories()), array_filter([$template?->category])))],
            'level' => ['required', Rule::in(BotTemplate::LEVELS)],
            'status' => ['required', Rule::in(BotTemplate::STATUSES)],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:'.(int) config('templates.image_max_kb', 5120)],
            'template_zip' => [$template ? 'nullable' : 'required', 'file', 'max:'.(int) config('templates.zip_max_kb', 51200)],
            'access_type' => ['required', Rule::in(['free', 'paid'])],
            'included_plan' => ['nullable', Rule::in(['free', 'pro', 'business'])],
            'price' => ['required_if:access_type,paid', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'max:10'],
            'marketplace_status' => ['required', Rule::in(['listed', 'unlisted', 'featured', 'archived'])],
            'demo_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $publishing = ($data['status'] ?? 'draft') === 'published'
            || in_array($data['marketplace_status'] ?? 'unlisted', ['listed', 'featured'], true);

        if ($publishing && ! $request->hasFile('image') && ! filled($template?->thumbnail_path)) {
            throw ValidationException::withMessages(['image' => 'Template image is required before listing or publishing.']);
        }

        if ($publishing && strlen(trim((string) ($data['description'] ?? ''))) < 20) {
            throw ValidationException::withMessages(['description' => 'Template description must be at least 20 characters before listing or publishing.']);
        }

        if ($publishing && ! $request->hasFile('template_zip') && ! $template?->commands()->exists()) {
            throw ValidationException::withMessages(['template_zip' => 'Upload a template file with at least one command before listing or publishing.']);
        }

        if (($data['access_type'] ?? 'free') === 'paid' && (float) ($data['price'] ?? 0) <= 0) {
            throw ValidationException::withMessages(['price' => 'Paid templates must have a price greater than zero.']);
        }

        if (($data['access_type'] ?? 'free') === 'free') {
            $data['price'] = 0;
        }

        return $data;
    }

    private function shortDescFrom(array $data): ?string
    {
        $source = trim(strip_tags((string) ($data['description'] ?? '')));

        if ($source === '') {
            return null;
        }

        return Str::limit($source, 200, '');
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $i    = 2;

        while (
            BotTemplate::where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }

    private function storeImage(Request $request, ?BotTemplate $template = null): ?string
    {
        if (! $request->hasFile('image')) {
            return null;
        }

        $path = $request->file('image')->store('templates/images', 'public');

        if ($template?->thumbnail_path && $template->thumbnail_path !== $path) {
            Storage::disk('public')->delete($template->thumbnail_path);
        }

        return $path;
    }

    private function templateCategories(): array
    {
        $path = config_path('template_categories.php');

        return file_exists($path) ? (require $path) : [];
    }

    private function isPublishable(BotTemplate $template): bool
    {
        return filled($template->thumbnail_path)
            && strlen(trim((string) $template->description)) >= 20
            && filled($template->template_zip_path)
            && ($template->isFree() || (float) $template->price > 0)
            && $template->commands()->exists();
    }
}
