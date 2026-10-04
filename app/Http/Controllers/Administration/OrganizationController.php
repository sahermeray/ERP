<?php

namespace App\Http\Controllers\Administration;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrganizationController extends Controller
{
    public function edit(Request $request): View
    {
        return view('administration.organization', [
            'organization' => $this->currentOrganization($request),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $organization = $this->currentOrganization($request);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:32'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        $organization->update($data);

        return redirect()->route('admin.organization.edit')->with('status', __('administration.organization_saved'));
    }

    private function currentOrganization(Request $request): Organization
    {
        return $request->user()->currentOrganization() ?? abort(404);
    }
}
