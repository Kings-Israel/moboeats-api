<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Motorbikes;
use App\Models\Restaurant;
use App\Models\RestaurantMotorbike;
use App\Models\User;
use Illuminate\Http\Request;

class MotorbikeController extends Controller
{
    // Fetch all motorbikes
    public function index(Request $request)
    {
        $per_page = $request->query('per_page');
        $make = $request->query('make');

        $motorbikes = Motorbikes::query()->with('currentRider', 'restaurants');

        if ($make) {
            $motorbikes->where('make', $make);
        }

        if ($per_page) {
            $motorbikes = $motorbikes->paginate($per_page);
        } else {
            $motorbikes = $motorbikes->get();
        }

        // Fetch riders to user for assigning
        $riders = User::whereHas('roles', function ($query) {
            $query->where('name', 'rider');
        })->get();

        // Fetch branches to assign to
        $restaurants = Restaurant::all();

        return response()->json([
            'motorbikes' => $motorbikes,
            'riders' => $riders,
            'restaurants' => $restaurants,
        ]);
    }

    // Fetch a single motorbike by ID
    public function show($id)
    {
        $motorbike = Motorbikes::find($id);
        if (!$motorbike) {
            return response()->json(['message' => 'Motorbike not found'], 404);
        }
        return response()->json($motorbike);
    }

    // Create a new motorbike
    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'make' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'registration_number' => 'required|string|max:255',
            'insurer' => 'nullable|string|max:255',
            'insurance_number' => 'nullable|string|max:255',
            'next_service_date' => 'nullable|date',
        ]);

        $motorbike = Motorbikes::create($validatedData);

        return response()->json($motorbike, 201);
    }

    // Update an existing motorbike
    public function update(Request $request, $id)
    {
        $motorbike = Motorbikes::find($id);
        if (!$motorbike) {
            return response()->json(['message' => 'Motorbike not found'], 404);
        }

        $validatedData = $request->validate([
            'make' => 'sometimes|required|string|max:255',
            'model' => 'sometimes|required|string|max:255',
            'registration_number' => 'sometimes|required|string|max:255',
            'insurer' => 'nullable|string|max:255',
            'insurance_number' => 'nullable|string|max:255',
            'next_service_date' => 'nullable|date',
        ]);

        $motorbike->update($validatedData);

        return response()->json($motorbike);
    }

    public function assignToRider(Request $request, $id)
    {
        $motorbike = Motorbikes::find($id);
        if (!$motorbike) {
            return response()->json(['message' => 'Motorbike not found'], 404);
        }

        $validatedData = $request->validate([
            'rider_id' => 'required|exists:users,id',
        ]);

        $motorbike->user_id = $validatedData['rider_id'];
        $motorbike->save();

        $user = User::find($validatedData['rider_id']);

        // Save to logs
        activity()
            ->performedOn($motorbike)
            ->causedBy(auth()->user())
            ->log('assigned motorbike ' . $motorbike->registration_number . ' to rider ' . $user->name);

        return response()->json($motorbike);
    }

    public function assignToRestaurant(Request $request, $id)
    {
        $motorbike = Motorbikes::find($id);
        if (!$motorbike) {
            return response()->json(['message' => 'Motorbike not found'], 404);
        }

        $validatedData = $request->validate([
            'restaurant_id' => 'required|exists:restaurants,id',
        ]);

        // Check if the motorbike is already assigned to the restaurant
        $existingAssignment = RestaurantMotorbike::where('motorbike_id', $motorbike->id)
            ->where('restaurant_id', $validatedData['restaurant_id'])
            ->first();

        if ($existingAssignment) {
            return response()->json(['message' => 'Motorbike is already assigned to this restaurant'], 400);
        }

        RestaurantMotorbike::create(
            ['motorbike_id' => $motorbike->id, 'restaurant_id' => $validatedData['restaurant_id']]
        );

        $restaurant = Restaurant::find($validatedData['restaurant_id']);

        // Save to logs
        activity()
            ->performedOn($motorbike)
            ->causedBy(auth()->user())
            ->log('assigned motorbike ' . $motorbike->registration_number . ' to restaurant ' . $restaurant->name);

        return response()->json($motorbike);
    }
}
