<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Restaurant;
use App\Models\RestaurantDocument;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;

class RestaurantDocumentsController extends Controller
{
    use HttpResponses;

    public function index($id)
    {
        $restaurant = Restaurant::find($id);

        return $this->success($restaurant->documents);
    }

    public function store(Request $request, $id)
    {
        $request->validate([
            'names' => ['nullable', 'array'],
            'names.*' => ['nullable', 'string'],
            'files' => ['required', 'array'],
            'files.*' => ['mimes:pdf']
        ]);

        $restaurant = Restaurant::find($id);

        foreach($request->files as $key => $file) {
            RestaurantDocument::create([
                'restaurant_id' => $restaurant->id,
                'file_url' => pathinfo($file->store('documents', 'restaurant'), PATHINFO_BASENAME),
                'document_name' => $request->names[$key] ? $request->names[$key] : NULL,
            ]);
        }

        return $this->success($restaurant->load('documents'), 'Documents saved successfully');
    }

    public function edit(Request $request, $id)
    {
        $request->validate([
            'file' => ['mimes:pdf'],
            'name' => ['nullable', 'string']
        ]);

        $document = RestaurantDocument::find($id);

        if (!$document) {
            return $this->error('', 'Document not found', 404);
        }

        $document->update([
            'document_name' => $request->has('name') && $request->name != '' ? $request->name : $document->document_name,
            'file_url' => pathinfo($request->file->store('documents', 'restaurant'), PATHINFO_BASENAME)
        ]);

        $documents = RestaurantDocument::where('restaurant_id', $document->restaurant_id)->get();

        return $this->success($documents, 'Document updated successfully');
    }
}
