<?php

namespace App\Traits\Admin;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Image;
// use Intervention\Image\ImageManager;
//
trait UploadFileTrait
{
    public function generateFileName($file){
        $fileNameWithExtension = $file->getClientOriginalName();
        $fileNameOnly = pathinfo($fileNameWithExtension, PATHINFO_FILENAME);
        $fileExtensionOnly = $file->getClientOriginalExtension();
        $fileNameToStore = $fileNameOnly.'_'.time().'.'.$fileExtensionOnly;
        if($this->pKey != null){
            $this->prevRecord = $this->defaultModel::where($this->pKeyCol,$this->pKey)->first();
        }
        return $fileNameToStore;
    }
    public function uploadFile($data)
    {
        // Log::info($data);
        $storageName = explode('\\',$data['storageName']);
        // Log::info($storageName);
        if(count($storageName) > 1){
            $path = config('app.storagePaths')[$storageName[0]][$storageName[1]]['storePath'];
            $disk = config('app.storagePaths')[$storageName[0]][$storageName[1]]['disk'];
        }else{
            $path = config('app.storagePaths')[$storageName[0]]['storePath'];
            $disk = config('app.storagePaths')[$storageName[0]]['disk'];
        }
        $fileName = $data['fileName'];
        $file = $data['file'];
        $prevFile = $data['prevFile'] != null? $data['prevFile']:null;
        //delete previous file
        if($prevFile != null){
            if(Storage::disk($disk)->exists($path.$prevFile)){
                Storage::disk($disk)->delete($path.$prevFile);
            }
        }
        //save new file
        $isSuccess = $file->storeAs($path, $fileName, $disk);
        if($isSuccess){
            return true;
        }
        return false;
    }
    public function uploadFileWithWatermark($data)
    {
        $storageName = explode('\\',$data['storageName']);
        $file = $data['file'];
        $extension = $file->getClientOriginalExtension();
        // Log::info("File Extension: " .$extension);
        if ($extension == 'jpeg' || $extension == 'png' || $extension == 'gif' || $extension == 'jpg' ){
            if(count($storageName) > 1){
                $path = config('app.storagePaths')[$storageName[0]][$storageName[1]]['readPath'];
                $disk = config('app.storagePaths')[$storageName[0]][$storageName[1]]['disk'];
            }else{
                $path = config('app.storagePaths')[$storageName[0]]['readPath'];
                $disk = config('app.storagePaths')[$storageName[0]]['disk'];
            }
        }

        $fileName = $data['fileName'];

        $imgFile = Image::make($file->getRealPath());
        $imgFile->text('© ' .date('Y'). ' ' .config('app.company.website').'', 60, 5, function($font) {
            $font->size(40);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('bottom');
            $font->angle(90);
        });
        $height = $imgFile->height();
        $width = $imgFile->width();

        info('height: '.$height); //856
        info('width: '.$width); //1280

        if ($width < 1280) {
            $imgFile->resize(1280, 856, function ($c) {
                $c->aspectRatio();
                // $c->upsize();
            });
        }
       

        $prevFile = $data['prevFile'] != null? $data['prevFile']:null;
        //delete previous file
        if($prevFile != null){
            if(count($storageName) > 1){
                $path = config('app.storagePaths')[$storageName[0]][$storageName[1]]['storePath'];
                $disk = config('app.storagePaths')[$storageName[0]][$storageName[1]]['disk'];
            }else{
                $path = config('app.storagePaths')[$storageName[0]]['storePath'];
                $disk = config('app.storagePaths')[$storageName[0]]['disk'];
            }
            if(Storage::disk($disk)->exists($path.$prevFile)){
                Storage::disk($disk)->delete($path.$prevFile);
            }
        }
        //save new file
        // $isSuccess = $imgFile->storeAs($path, $fileName, $disk);
        // $isSuccess = $file->save(public_path($path).'/'.$fileName);
        $isSuccess = $imgFile->save(public_path($path).'/'.$fileName);
        if($isSuccess){
            return true;
        }
        return false;
    }
    public function deleteFile($data){
        $storageName = explode('\\',$data['storageName']);
        Log::info($storageName);
        if(count($storageName) > 1){
            $path = config('app.storagePaths')[$storageName[0]][$storageName[1]]['storePath'];
            $disk = config('app.storagePaths')[$storageName[0]][$storageName[1]]['disk'];
        }else{
            $path = config('app.storagePaths')[$storageName[0]]['storePath'];
            $disk = config('app.storagePaths')[$storageName[0]]['disk'];
        }
        $fileName = $data['fileName'];
        if(Storage::disk($disk)->exists($path.$fileName)){
            Storage::disk($disk)->delete($path.$fileName);
        }
    }
}
