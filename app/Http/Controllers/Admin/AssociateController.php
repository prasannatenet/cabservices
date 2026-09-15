<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AssociateController extends Controller
{
    public function index(Request $request): View
    {
        $associates = User::where('role', User::ROLE_ASSOCIATE)
            ->with('assignedCities')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->query('search');
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->filled('city_id'), fn ($query) => $query->whereHas(
                'assignedCities',
                fn ($q) => $q->where('cities.id', $request->query('city_id'))
            ))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.associates.index', [
            'associates' => $associates,
            'cities' => City::orderBy('name')->get(),
            'statuses' => ['Active', 'Inactive'],
        ]);
    }

    public function create(): View
    {
        return view('admin.associates.create', [
            'cities' => City::orderBy('name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $associate = User::create([
            'name' => $validated['name'],
            'username' => $validated['login_id'],
            'email' => $validated['email'] ?? null,
            'password' => $validated['password'],
            'status' => $validated['status'],
            'role' => User::ROLE_ASSOCIATE,
        ]);

        $associate->assignedCities()->sync($validated['city_ids']);

        return redirect()->route('admin.associates.index')->with('success', 'Associate created successfully.');
    }

    public function edit(User $associate): View
    {
        $this->ensureIsAssociate($associate);

        return view('admin.associates.edit', [
            'associate' => $associate->load('assignedCities'),
            'cities' => City::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $associate)
    {
        $this->ensureIsAssociate($associate);

        $validated = $request->validate($this->rules($associate));

        $attributes = [
            'name' => $validated['name'],
            'username' => $validated['login_id'],
            'email' => $validated['email'] ?? null,
            'status' => $validated['status'],
        ];

        if (! empty($validated['password'])) {
            $attributes['password'] = $validated['password'];
        }

        $associate->update($attributes);

        $associate->assignedCities()->sync($validated['city_ids']);

        return redirect()->route('admin.associates.index')->with('success', 'Associate updated successfully.');
    }

    /**
     * Deleting an associate only removes his login. The fleet, drivers and
     * services he added stay in the cities he managed and remain visible to the admin.
     */
    public function destroy(User $associate)
    {
        $this->ensureIsAssociate($associate);

        $associate->assignedCities()->detach();
        $associate->delete();

        return redirect()->route('admin.associates.index')->with(
            'success',
            'Associate deleted successfully. His fleet, drivers and services remain in the admin panel.'
        );
    }

    /**
     * Validation rules shared by store and update.
     *
     * @return array<string, mixed>
     */
    private function rules(?User $associate = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'login_id' => [
                'required', 'alpha_dash', 'min:3', 'max:255',
                Rule::unique('users', 'username')->ignore($associate?->id),
            ],
            'email' => [
                'nullable', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($associate?->id),
            ],
            'password' => [$associate ? 'nullable' : 'required', 'string', 'min:8'],
            // At least one city: he is the admin of those cities only.
            'city_ids' => ['required', 'array', 'min:1'],
            'city_ids.*' => ['integer', Rule::exists('cities', 'id')],
            'status' => ['required', Rule::in(['Active', 'Inactive'])],
        ];
    }

    /**
     * The associates resource only ever handles associate accounts, so the
     * route can never be used to edit or delete the admin's own account.
     */
    private function ensureIsAssociate(User $user): void
    {
        abort_unless($user->isAssociate(), 404);
    }
}
