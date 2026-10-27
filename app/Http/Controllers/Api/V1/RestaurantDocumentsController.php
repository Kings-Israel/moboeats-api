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

/**
 * @group Restaurant Management
 *
 * Business documents (PDF) uploaded by restaurant owners.
 */
class RestaurantDocumentsController extends Controller
{
    use HttpResponses;

    /**
     * List restaurant documents
     *
     * Returns all documents uploaded for a restaurant.
     *
     * @urlParam id integer required The numeric ID of the restaurant. Example: 1
     *
     * @response 200 {"status": "Request was succesful", "message": null, "data": [{"id": 1, "restaurant_id": 1, "document_name": "Business Permit", "file_url": "business-permit-65a1b2c3d4e5f.pdf", "created_at": "2024-01-10T10:00:00.000000Z", "updated_at": "2024-01-10T10:00:00.000000Z"}]}
     * @response 422 scenario="Restaurant owner has no restaurant" {"data": "no_restaurant"}
     * @responseField data object[] The restaurant's documents.
     */
    public function index($id)
    {
        $restaurant = Restaurant::find($id);

        return $this->success($restaurant->documents);
    }

    /**
     * Upload restaurant documents
     *
     * Uploads PDF business documents for a restaurant. If the restaurant is `Denied` it is moved back to pending, and for
     * pending/denied restaurants the admin is notified to review it. The activity is logged.
     *
     * @urlParam uuid string required The UUID of the restaurant. Example: 5e6f7a8b-9c0d-4e1f-a2b3-c4d5e6f7a8b9
     * @bodyParam files file[] required One or more PDF documents.
     * @bodyParam names string[] Optional display names, matched to `files` by index. Example: ["Business Permit"]
     *
     * @response 200 {"status": "Request was succesful", "message": "Documents saved successfully", "data": {"id": 1, "uuid": "5e6f7a8b-9c0d-4e1f-a2b3-c4d5e6f7a8b9", "user_id": 3, "name": "Mama Oliech", "email": "info@mamaoliech.co.ke", "phone_no": "254712345678", "address": "Marcus Garvey Rd", "city": "Nairobi", "logo": "https://moboeats.com/storage/restaurant/logos/logo.png", "status": "Pending", "created_at": "2024-01-10T10:00:00.000000Z", "updated_at": "2024-01-10T10:00:00.000000Z", "documents": [{"id": 1, "restaurant_id": 1, "document_name": "Business Permit", "file_url": "business-permit-65a1b2c3d4e5f.pdf", "created_at": "2024-01-10T10:00:00.000000Z", "updated_at": "2024-01-10T10:00:00.000000Z"}]}}
     * @response 422 scenario="Validation error" {"message": "The files field is required.", "errors": {"files": ["The files field is required."]}}
     * @response 422 scenario="Restaurant owner has no restaurant" {"data": "no_restaurant"}
     * @responseField data object The restaurant with its `documents`.
     */
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

    /**
     * Replace restaurant documents
     *
     * Deletes all existing documents of the restaurant and uploads the given PDF documents. If the restaurant is `Denied`
     * it is moved back to pending, and for pending/denied restaurants the admin is notified. The activity is logged.
     *
     * @urlParam uuid string required The UUID of the restaurant. Example: 5e6f7a8b-9c0d-4e1f-a2b3-c4d5e6f7a8b9
     * @bodyParam files file[] required One or more PDF documents.
     * @bodyParam names string[] Optional display names, matched to `files` by index. Example: ["Business Permit"]
     *
     * @response 200 {"status": "Request was succesful", "message": "Documents saved successfully", "data": {"id": 1, "uuid": "5e6f7a8b-9c0d-4e1f-a2b3-c4d5e6f7a8b9", "user_id": 3, "name": "Mama Oliech", "email": "info@mamaoliech.co.ke", "phone_no": "254712345678", "address": "Marcus Garvey Rd", "city": "Nairobi", "logo": "https://moboeats.com/storage/restaurant/logos/logo.png", "status": "Pending", "created_at": "2024-01-10T10:00:00.000000Z", "updated_at": "2024-01-10T10:00:00.000000Z", "documents": [{"id": 1, "restaurant_id": 1, "document_name": "Business Permit", "file_url": "business-permit-65a1b2c3d4e5f.pdf", "created_at": "2024-01-10T10:00:00.000000Z", "updated_at": "2024-01-10T10:00:00.000000Z"}]}}
     * @response 422 scenario="Validation error" {"message": "The files field is required.", "errors": {"files": ["The files field is required."]}}
     * @response 422 scenario="Restaurant owner has no restaurant" {"data": "no_restaurant"}
     * @responseField data object The restaurant with its `documents`.
     */
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

    /**
     * Download a restaurant document
     *
     * Downloads a single restaurant document file.
     *
     * @urlParam id integer required The numeric ID of the document. Example: 1
     *
     * @response 200 scenario="File download" <<binary>> The document (PDF) file.
     * @response 422 scenario="Restaurant owner has no restaurant" {"data": "no_restaurant"}
     */
    public function download($file)
    {
        $document = RestaurantDocument::find($file);

        return Storage::disk('restaurant')->download('/documents/'.$document->file_url, $document->document_name);
    }
}
