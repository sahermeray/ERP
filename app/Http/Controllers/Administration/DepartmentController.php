<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $organization = $this->currentOrganization($request);
        $departments = $organization->departments()
            ->withCount(['memberships', 'children', 'sendingDocuments', 'receivingDocuments'])
            ->orderBy('name')
            ->paginate(15);

        return view('administration.departments.index', compact('departments', 'organization'));
    }

    public function create(Request $request): View
    {
        return view('administration.departments.form', [
            'organization' => $this->currentOrganization($request),
            'department' => new Department(['is_active' => true]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $organization = $this->currentOrganization($request);
        $organization->departments()->create($this->validatedDepartment($request, $organization));

        return redirect()->route('admin.departments.index')->with('status', 'Department added.');
    }

    public function edit(Request $request, int $department): View
    {
        $organization = $this->currentOrganization($request);

        return view('administration.departments.form', [
            'organization' => $organization,
            'department' => $organization->departments()->findOrFail($department),
        ]);
    }

    public function update(Request $request, int $department): RedirectResponse
    {
        $organization = $this->currentOrganization($request);
        $record = $organization->departments()->findOrFail($department);
        $record->update($this->validatedDepartment($request, $organization, $record));

        return redirect()->route('admin.departments.index')->with('status', 'Department updated.');
    }

    public function updateStatus(Request $request, int $department): RedirectResponse
    {
        $organization = $this->currentOrganization($request);
        $record = $organization->departments()->findOrFail($department);
        $data = $request->validate(['is_active' => ['required', 'boolean']]);
        $record->update($data);

        return redirect()->route('admin.departments.index')->with('status', 'Department status updated.');
    }

    public function destroy(Request $request, int $department): RedirectResponse
    {
        $organization = $this->currentOrganization($request);
        $record = $organization->departments()->findOrFail($department);

        if ($record->memberships()->exists()
            || $record->children()->exists()
            || $record->sendingDocuments()->exists()
            || $record->receivingDocuments()->exists()) {
            return redirect()->route('admin.departments.index')->withErrors([
                'department' => 'This department is assigned to a user or document and cannot be deleted. Deactivate it instead.',
            ]);
        }

        $record->delete();

        return redirect()->route('admin.departments.index')->with('status', 'Department deleted.');
    }

    private function currentOrganization(Request $request): Organization
    {
        return $request->user()->currentOrganization() ?? abort(404);
    }

    /** @return array{name: string, code: ?string, description: ?string, is_active: bool} */
    private function validatedDepartment(Request $request, Organization $organization, ?Department $department = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'nullable',
                'string',
                'max:64',
                Rule::unique('departments', 'code')
                    ->where('organization_id', $organization->id)
                    ->ignore($department?->id),
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['required', 'boolean'],
        ]);
        $data['code'] = filled($data['code'] ?? null) ? $data['code'] : null;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
