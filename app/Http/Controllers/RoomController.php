<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Room;
use App\Models\Conflict;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->get('search');
        $rooms  = Room::when($search, fn($q) => $q
                ->where('room_number', 'like', "%{$search}%")
                ->orWhere('building', 'like', "%{$search}%")
                ->orWhere('type', 'like', "%{$search}%")
            )
            ->paginate(10)
            ->withQueryString();
        return view('admin.rooms.index', compact('rooms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'room_number' => 'required|string|max:20|unique:rooms,room_number',
            'building'    => 'required|string|max:255',
            'type'        => 'required|in:classroom,lab,seminar_hall',
            'capacity'    => 'required|integer|min:1|max:500',
            'equipment'   => 'nullable|string|max:255',
            'status'      => 'required|in:active,inactive',
        ]);

        Room::create([
            'room_number' => $request->room_number,
            'building'    => $request->building,
            'type'        => $request->type,
            'capacity'    => $request->capacity,
            'equipment'   => $request->equipment,
            'status'      => $request->status,
            'created_at'  => now(),
        ]);

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Room added successfully.');
    }

    public function update(Request $request, Room $room)
    {
        $request->validate([
            'room_number' => 'required|string|max:20|unique:rooms,room_number,' . $room->id,
            'building'    => 'required|string|max:255',
            'type'        => 'required|in:classroom,lab,seminar_hall',
            'capacity'    => 'required|integer|min:1|max:500',
            'equipment'   => 'nullable|string|max:255',
            'status'      => 'required|in:active,inactive',
        ]);

        $room->update([
            'room_number' => $request->room_number,
            'building'    => $request->building,
            'type'        => $request->type,
            'capacity'    => $request->capacity,
            'equipment'   => $request->equipment,
            'status'      => $request->status,
        ]);

        return redirect()->route('admin.rooms.index')
            ->with('success', 'Room updated successfully.');
    }

    public function destroy(Room $room)
    {
        // room_availability has no further children
        $room->roomAvailabilities()->delete();

        // timetable_slots → conflicts
        $slotIds = $room->timetableSlots()->pluck('id');
        if ($slotIds->isNotEmpty()) {
            Conflict::whereIn('slot_id_1', $slotIds)
                    ->orWhereIn('slot_id_2', $slotIds)
                    ->delete();
        }
        $room->timetableSlots()->delete();

        $room->delete();
        return redirect()->route('admin.rooms.index')
            ->with('success', 'Room deleted successfully.');
    }
}
