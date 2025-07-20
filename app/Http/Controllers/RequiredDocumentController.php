<?php

namespace App\Http\Controllers;

use App\Models\RequiredDocument;
use App\Traits\HttpResponses;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class RequiredDocumentController extends Controller
{
    use HttpResponses;

    public function index(Request $request)
    {
        $section = $request->query('section');

        $required_documents = RequiredDocument::when($section && $section != '', function ($query) use ($section) {
            $query->where('section', $section);
        })
        ->get();

        return $this->success($required_documents);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'section' => ['required'],
            'name' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Invalid Data', 400);
        }

        $required_doc = RequiredDocument::create([
            'section' => $request->section,
            'name' => $request->name,
            'has_expiry' => $request->has_expiry
        ]);

        return $this->success($required_doc, 'Document added successfully', 201);
    }

    public function update(Request $request, RequiredDocument $required_document)
    {
        $validator = Validator::make($request->all(), [
            'section' => ['required'],
            'name' => ['required']
        ]);

        if ($validator->fails()) {
            return $this->error($validator->messages(), 'Invalid Data', 400);
        }

        $required_document->update([
            'section' => $request->section,
            'name' => $request->name,
            'has_expiry' => $request->has_expiry
        ]);

        return $this->success($required_document, 'Document updated successfully', 200);
    }

    public function delete(RequiredDocument $required_document)
    {
        $required_document->delete();

        return $this->success($required_document, 'Document deleted successfully');
    }
}
