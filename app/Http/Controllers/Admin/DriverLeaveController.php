<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Models\DriverLeave;
use Illuminate\Http\Request;

class DriverLeaveController extends Controller
{
    public function index(Driver $driver)
    {
        $leaves = $driver->leaves()->orderBy('start_date', 'desc')->paginate(15);

        return view('admin.drivers.leaves', compact('driver', 'leaves'));
    }

    public function store(Request $request, Driver $driver)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string|max:500',
            'status' => 'required|in:Pending,Approved,Rejected',
        ]);

        $driver->leaves()->create($validated);

        return redirect()->route('admin.drivers.leaves', $driver)->with('success', 'Leave added.');
    }

    public function update(Request $request, Driver $driver, DriverLeave $leave)
    {
        $validated = $request->validate([
            'status' => 'required|in:Pending,Approved,Rejected',
        ]);

        $leave->update($validated);

        return redirect()->route('admin.drivers.leaves', $driver)->with('success', 'Leave status updated.');
    }

    public function destroy(Driver $driver, DriverLeave $leave)
    {
        $leave->delete();

        return redirect()->route('admin.drivers.leaves', $driver)->with('success', 'Leave deleted.');
    }
}
