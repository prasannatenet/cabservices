<?php

namespace App\Http\Controllers\Associate;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverLeave;
use Illuminate\Http\Request;

class DriverLeaveController extends Controller
{
    public function index(Driver $driver)
    {
        $this->authorizeDriver($driver);

        $leaves = $driver->leaves()->orderBy('start_date', 'desc')->paginate(15);

        return view('associate.drivers.leaves', compact('driver', 'leaves'));
    }

    public function store(Request $request, Driver $driver)
    {
        $this->authorizeDriver($driver);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'status' => 'required|in:Pending,Approved,Rejected',
        ]);

        $driver->leaves()->create($validated);

        return redirect()->route('associate.drivers.leaves', $driver)->with('success', 'Leave added.');
    }

    public function update(Request $request, Driver $driver, DriverLeave $leave)
    {
        $this->authorizeDriver($driver);

        $validated = $request->validate([
            'status' => 'required|in:Pending,Approved,Rejected',
        ]);

        $leave->update($validated);

        return redirect()->route('associate.drivers.leaves', $driver)->with('success', 'Leave status updated.');
    }

    public function destroy(Driver $driver, DriverLeave $leave)
    {
        $this->authorizeDriver($driver);

        $leave->delete();

        return redirect()->route('associate.drivers.leaves', $driver)->with('success', 'Leave deleted.');
    }

    /**
     * Stop the associate from managing leave of a driver he does not manage.
     */
    private function authorizeDriver(Driver $driver): void
    {
        abort_unless(
            auth()->user()->managesCity($driver->current_city_id),
            403,
            'This driver belongs to a city you do not manage.'
        );
    }
}
