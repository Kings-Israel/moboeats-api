<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantDocument;
use App\Models\User;
use App\Notifications\UpdatedRestaurant;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RestaurantDocumentsController extends Controller
{
    use HttpResponses;

    public function index($id)
    {
        $restaurant = Restaurant::find($id);

        return $this->success($restaurant->documents);
    }

    public function store(Request $request, $uuid)
    {
        $request->validate([
            'names' => ['nullable', 'array'],
            'names.*' => ['nullable', 'string'],
            'files' => ['required', 'array'],
            'files.*' => ['mimes:pdf']
        ]);

        $restaurant = Restaurant::where('uuid', $uuid)->first();

        foreach($request->files as $file) {
            if (is_array($file)) {
                foreach($file as $key => $data) {
                    $originalFilename = pathinfo($data->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $newFilename = $originalFilename.'-'.uniqid().'.'.$data->guessExtension();
                    $data->move('storage/restaurant/documents', $newFilename);
                    RestaurantDocument::create([
                        'restaurant_id' => $restaurant->id,
                        'file_url' => $newFilename,
                        'document_name' => $request->names[$key] ? $request->names[$key] : NULL,
                    ]);
                }
            }
        }

        if ($restaurant->status == 'Pending' || $restaurant->status == 'Denied') {
            // Update restaurant status to pending
            if ($restaurant->status == 'Denied') {
                $restaurant->update([
                    'status' => '1'
                ]);
            }

            // Notify admin to review the restaurant
            $admin = User::where('email', 'admin@moboeats.com')->first();
            $admin->notify(new UpdatedRestaurant($restaurant));
        }

        activity()->causedBy(auth()->user())->performedOn($restaurant)->log('uploaded business documents');

        return $this->success($restaurant->load('documents'), 'Documents saved successfully');
    }

    public function update(Request $request, $uuid)
    {
        $request->validate([
            'names' => ['nullable', 'array'],
            'names.*' => ['nullable', 'string'],
            'files' => ['required', 'array'],
            'files.*' => ['mimes:pdf']
        ]);

        $restaurant = Restaurant::where('uuid', $uuid)->first();

        $docs = RestaurantDocument::where('restaurant_id', $restaurant->id)->get();

        foreach ($docs as $doc) {
            Storage::disk('restaurant')->delete('/documents/'.$doc->file_url);
            $doc->delete();
        }

        foreach($request->files as $file) {
            if (is_array($file)) {
                foreach($file as $key => $data) {
                    $originalFilename = pathinfo($data->getClientOriginalName(), PATHINFO_FILENAME);
                    // this is needed to safely include the file name as part of the URL
                    $newFilename = $originalFilename.'-'.uniqid().'.'.$data->guessExtension();
                    $data->move('storage/restaurant/documents', $newFilename);
                    RestaurantDocument::create([
                        'restaurant_id' => $restaurant->id,
                        'file_url' => $newFilename,
                        'document_name' => $request->names[$key] ? $request->names[$key] : NULL,
                    ]);
                }
            }
        }

        if ($restaurant->status == 'Pending' || $restaurant->status == 'Denied') {
            // Update restaurant status to pending
            if ($restaurant->status == 'Denied') {
                $restaurant->update([
                    'status' => '1'
                ]);
            }
            // Notify admin to review the restaurant
            $admin = User::where('email', 'admin@moboeats.com')->first();
            $admin->notify(new UpdatedRestaurant($restaurant));
        }

        activity()->causedBy(auth()->user())->performedOn($restaurant)->log('updated business documents');

        return $this->success($restaurant->load('documents'), 'Documents saved successfully');
    }

    public function download($file)
    {
        $document = RestaurantDocument::find($file);

        return Storage::disk('restaurant')->download('/documents/'.$document->file_url, $document->document_name);
    }
}
