<?php

namespace App\Traits\Admin;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Schema;

trait LayoutTrait
{
    public $listData = [],$cardData,$viewData= [], $prevRecord = [], $searchParams = [], $return, $actionRoutes = [],$defaultModel,$setup,$isSearch, $isReltionship, $relationName;
    public $pkeyCol, $pKey =null,$isCommit = true,$pageTitle;
    public $defReportPage = 'Admin/Reports/Filters/Default';
    //
    public function def_constructor(){
        $this->defaultModel = $this->settings['model'];
        $this->setup['columns'] = $this->defaultModel::getColumns();
        $this->setup['settings'] = $this->settings;
        $instance = new $this->defaultModel;
        $this->pKeyCol = $instance->getKeyName();
    }
    public function def_index()
    {
        $this->setup['pageType'] = 'list';
        $this->setup['pageTitle'] = $this->settings['caption'].' List';
        $this->searchParams();
        if(!$this->isSearch){
            if ($this->isReltionship) {
                // Log::info('Yes, Relation');
                if ($this->defaultModel == '\App\Models\Post\Post') {
                    // info('yes');
                    if ((Auth::user()->user_category != 100)) {
                        $this->listData = $this->defaultModel::with($this->relationName)
                        ->where('user_id', Auth::user()->id)
                        ->orderBy(
                        $this->settings['orderBy']['column'],
                        $this->settings['orderBy']['order'])->paginate(config('app.maxRecsPerPage'));
                    } else {
                        $this->listData = $this->defaultModel::with($this->relationName)
                        ->orderBy(
                            $this->settings['orderBy']['column'],
                            $this->settings['orderBy']['order'])->paginate(config('app.maxRecsPerPage')
                        );
                    }

                } else {

                    $this->listData = $this->defaultModel::with($this->relationName)
                    ->orderBy(
                        $this->settings['orderBy']['column'],
                        $this->settings['orderBy']['order'])->paginate(config('app.maxRecsPerPage')
                    );
                }
                
                // Log::info($this->listData);
            } else {
                // Log::info('Not, Relation');
                if ($this->defaultModel == 'Post') {
                    $this->listData = $this->defaultModel::with($this->relationName)
                    ->where('user_id', Auth::user()->id)
                    ->orderBy(
                    $this->settings['orderBy']['column'],
                    $this->settings['orderBy']['order'])->paginate(config('app.maxRecsPerPage'));
                } else {
                    $this->listData = $this->defaultModel::orderBy(
                        $this->settings['orderBy']['column'],
                        $this->settings['orderBy']['order'])->paginate(config('app.maxRecsPerPage')
                    );
                }
                
                
                // Log::info($this->listData);
            }

        }else{
            //$users = new stdClass();
            // Log::info('is Search');
            foreach($this->params as $param){
                if($param['column'] != 'all'){
                    $this->listData = $this->defaultModel::search(
                        $param['value'])
                        ->where($param['column'],'LIKE','%'.$param['value'].'%')
                        ->orderby($this->settings['orderBy']['column'],$this->settings['orderBy']['order'])
                        ->paginate(config('app.maxRecsPerPage')
                    );
                }else{
                    $this->listData = $this->defaultModel::search($param['value'])->orderby($this->settings['orderBy']['column'],$this->settings['orderBy']['order'])->paginate(config('app.maxRecsPerPage'));
                }
            }
        }
        $this->viewData = [
            'listData' => $this->listData,
            'setup' => $this->setup,
        ];
    }


    public function def_create(){
        $this->setup['pageType'] = 'create';
        $this->setup['pageTitle'] = 'Create '.$this->settings['caption'];
        $this->viewData = [
            'cardData' => null,
            'setup' => $this->setup,
            'other' => null,
        ];
    }
    public function defStore($data){
        //
    }
    public function defStoreAfter(){
        //
    }
    public function def_show($uuid){
        $this->setup['pageType'] = 'view';
        $this->setup['pageTitle'] = $this->settings['caption'].' Details';
        $cardData = $this->defaultModel::where('uuid',$uuid)->first();
        $this->viewData = [
            'cardData' => $cardData,
            'setup' => $this->setup,
            'selected' => $cardData != null? [$cardData->uuid.'#0']:[],
        ];
    }
    public function def_edit($uuid){
        $this->setup['pageType'] = 'edit';
        $this->setup['pageTitle'] = 'Edit '.$this->settings['caption'];
        if ($this->isReltionship) {
            // info($this->relationName);
            $cardData = $this->defaultModel::with($this->relationName)->where('uuid',$uuid)->first();
        } else {
            $cardData = $this->defaultModel::where('uuid',$uuid)->first();
        }
        $this->viewData = [
            'cardData' => $cardData,
            'setup' => $this->setup,
            'selected' => $cardData != null? [$cardData->uuid.'#0']:[],
        ];
    }
    public function searchParams(){
        // $currentUrl = url()->full();
        $currentUrl = Request::fullUrl();
        $this->isSearch = false;
        if (strpos($currentUrl, 'search=') !== false) {
            $url = explode('search=%20',$currentUrl);
            if(isset($url[1])){
                $this->isSearch = true;
                $searchQuery = $url[1];
                $query = explode('%20',$searchQuery);
                if(count($query) > 0){
                    foreach($query as $set){
                        $pair = explode('%3D',$set);
                        $column = $pair[0];
                        $value = $pair[1];
                        $this->params[] = ['column' => $column, 'value' => $value];
                    }
                }
            }
        }
    }
    public function defReport(){
        $this->setup['pageType'] = 'report';
        $this->setup['pageTitle'] = $this->settings['caption'].' Report';
        $operators = [
            ['operator' => '=','caption' => 'Equals'],
            ['operator' => '!=','caption' => 'Not equals'],
            ['operator' => '>','caption' => 'Greater than'],
            ['operator' => '<','caption' => 'Less than'],
            ['operator' => '>=','caption' => 'Greater than or equal to'],
            ['operator' => '<=','caption' => 'Less than or equal to'],
            ['operator' => 'startsWith','caption' => 'Starts With'],
            ['operator' => 'endsWith','caption' => 'Ends With'],
            ['operator' => 'contains','caption' => 'Contains'],
        ];
        $dataItems = [];
        $dataItems[0] = ['model' => $this->setup['settings']['model'], 'columns' => $this->setup['columns'],'caption' => $this->setup['settings']['caption'].' Dataset'];
        $this->setup['columns'] = [];
        $this->viewData = [
            'setup' => $this->setup,
            'operators' => $operators,
            'dataItems' => $dataItems,
            'filterPage' => 'default',
            'previewUrl' => '/system/report/generate-report',
            'layoutView' => 'reports.admin.services'

        ];
    }
    public function notification($type){
        $notification = null;
        if($type == 'success'){
            $notification = ['type' => 'success', 'message' => $this->pKey == null? config('app.defaultErrors')['crud']['created']:config('app.defaultErrors')['crud']['updated']];
        }
        elseif($type == 'error'){
            $notification = ['type' => 'error', 'message' => config('app.defaultErrors')['default']];
        }
        elseif($type == 'deleteSuccess'){
            $notification = ['type' => 'success', 'message' => config('app.defaultErrors')['crud']['deleted']];
        }
        elseif($type == 'generalSuccess'){
            $notification = ['type' => 'success', 'message' => 'Operation completed successfully'];
        }
        return $notification;
    }
}