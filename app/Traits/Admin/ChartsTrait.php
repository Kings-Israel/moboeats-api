<?php

namespace App\Traits\Admin;

use Illuminate\Support\Facades\Schema;

trait ChartsTrait
{
    public function getChartOptions($desc){
        if($desc['chartType'] == 'line'){
            $options = $this->lineChart($desc);
        }
        return $options;
    }
    public function lineChart($desc){
        $options = [
            'title' => [
                'text' => $desc['title'],
                'left' => "center"
            ],
            'xAxis' => [
                'type' => 'category',
                'data' => []
            ],
            'yAxis' => [
                'type' => 'value'
            ],
            'series' => [
                [
                'data' => [],
                'type' => 'line'
                ]
            ]
        ];
        $data = $this->chartData($desc);
        $options['xAxis']['data'] = $data['durationList'];
        $options['series'][0]['data'] = $data['durationData'];
        return $options;
    }
    public function pieChart(){
        $options = [
            'title' => [
                'text' => "",
                'left' => "center"
            ],
            'tooltip' => [
                'trigger' => "item",
                'formatter' => "{a} <br/>{b} : {c} ({d}%)"
            ],
            'legend' => [
                'orient' => "vertical",
                'left' => "left",
                'data' => ["Direct", "Email", "Ad Networks", "Video Ads", "Search Engines"]
            ],
            'series' => [
                [
                'name' => "Traffic Sources",
                'type' => "pie",
                'radius' => "55%",
                'center' => ["50%", "60%"],
                'data' => [],
                'emphasis' => [
                    'itemStyle' => [
                    'shadowBlur' => 10,
                    'shadowOffsetX' => 0,
                    'shadowColor' => "rgba(0, 0, 0, 0.5)"
                    ]
                ]
                ]
            ]
        ];
        return $options;
    }
    public function chartData($desc){
        $data = null;
        if($desc['records'] != null){
            //weekly
            if($desc['duration'] == 'weekly'){
                $data = [
                    'durationData' => [0,0,0,0,0,0,0],
                    'durationList' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
                ];
                foreach($desc['records'] as $record){
                    //Counting
                    if($desc['operation'] == 'count'){
                        $dayName = $record[$desc['durationColumn']]->englishDayOfWeek;
                        if($dayName == 'Sunday'){
                            $data['durationData'][0] = $data['durationData'][0]+1;
                        }
                        elseif($dayName == 'Monday'){
                            $data['durationData'][1] = $data['durationData'][1]+1;
                        }
                        elseif($dayName == 'Tuesday'){
                            $data['durationData'][2] = $data['durationData'][2]+1;
                        }
                        elseif($dayName == 'Wednesday'){
                            $data['durationData'][3] = $data['durationData'][3]+1;
                        }
                        elseif($dayName == 'Thursday'){
                            $data['durationData'][4] = $data['durationData'][4]+1;
                        }
                        elseif($dayName == 'Friday'){
                            $data['durationData'][5] = $data['durationData'][5]+1;
                        }
                        elseif($dayName == 'Saturday'){
                            $data['durationData'][6] = $data['durationData'][6]+1;
                        }
                    }
                }
            }
            //monthly
            elseif($desc['duration'] == 'monthly'){
                $data = [
                    'durationData' => [0,0,0,0,0,0,0,0,0,0,0,0],
                    'durationList' => ['Jan','Feb','Mar','Apr','May','June','July','Aug','Sep','Oct','Nov','Dec',]
                ];
                foreach($desc['records'] as $record){
                    //Counting
                    if($desc['operation'] == 'count'){
                        $dayName = $record[$desc['durationColumn']]->englishMonth;
                        if($dayName == 'January'){
                            $data['durationData'][0] = $data['durationData'][0]+1;
                        }
                        elseif($dayName == 'February'){
                            $data['durationData'][1] = $data['durationData'][1]+1;
                        }
                        elseif($dayName == 'March'){
                            $data['durationData'][2] = $data['durationData'][2]+1;
                        }
                        elseif($dayName == 'April'){
                            $data['durationData'][3] = $data['durationData'][3]+1;
                        }
                        elseif($dayName == 'May'){
                            $data['durationData'][4] = $data['durationData'][4]+1;
                        }
                        elseif($dayName == 'June'){
                            $data['durationData'][5] = $data['durationData'][5]+1;
                        }
                        elseif($dayName == 'July'){
                            $data['durationData'][6] = $data['durationData'][6]+1;
                        }
                        elseif($dayName == 'August'){
                            $data['durationData'][7] = $data['durationData'][7]+1;
                        }
                        elseif($dayName == 'September'){
                            $data['durationData'][8] = $data['durationData'][8]+1;
                        }
                        elseif($dayName == 'October'){
                            $data['durationData'][9] = $data['durationData'][9]+1;
                        }
                        elseif($dayName == 'November'){
                            $data['durationData'][10] = $data['durationData'][10]+1;
                        }
                        elseif($dayName == 'December'){
                            $data['durationData'][11] = $data['durationData'][11]+1;
                        }
                    }
                }
            }
        }
        return $data;
    }
}
