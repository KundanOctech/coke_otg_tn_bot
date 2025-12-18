<?php

namespace App\Controllers;

use App\Constant\FlowConstant;
use App\Constant\GenericConstant;
use App\Models\AssuredRewardCode;
use App\Models\AssuredRewardType;
use App\Models\ClickTracker;
use App\Models\DailyCodeCount;
use App\Models\FlowToken;
use App\Models\Report;
use App\Models\UniqueCode;
use App\Models\User;
use App\Models\UserSession;
use App\Models\Winner;
use Illuminate\Database\QueryException;

class DashboardHelperController extends Controller
{
    protected $dashboardStartDate = '2025-12-10';
    protected $dashboardEndDate = '2026-12-31';


    protected function getStartDate($req)
    {
        $startDate = $req->getQueryParam('startDate');
        if (!empty($startDate) && strtotime($this->dashboardStartDate) < strtotime($startDate)) {
            return $startDate;
        }
        return $this->dashboardStartDate;
    }

    protected function getEndDate($req)
    {
        $todayDate = date('Y-m-d');
        $endDate = $req->getQueryParam('endDate');
        if (!empty($endDate) && strtotime($endDate) <= strtotime($todayDate)) {
            return $endDate;
        }
        if (!empty($endDate) && strtotime($this->dashboardEndDate) < strtotime($todayDate)) {
            return $this->dashboardEndDate;
        }
        return $todayDate;
    }

    protected function getWhereCount($startDate, $endDate, $class, $chartName, $where, $condition, $addRegister = false, $date = "created_date", $subName = null)
    {
        if (empty($subName)) {
            $subName = $chartName;
        }
        $preloadedList = Report::selectRaw('event_date as e_date, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $query = $class::where($date, $thisDate);
                if ($addRegister) {
                    $query->where("registered", 1);
                }
                $count = $query->where($where, $condition)
                    ->count();
                $outputObj[$thisDate] = $count;
                if ($i < $cutoffDate) {
                    $this->addReportData([
                        'event_date' => $thisDate,
                        'chart_key' => $chartName,
                        'sub_key' => $subName,
                        'event_count' => $count
                    ]);
                }
                unset($count);
            }
        }
        return $outputObj;
    }

    protected function getWheren3DCount($startDate, $endDate, $class, $chartName, $where, $condition, $date = "created_date", $subName = null)
    {
        if (empty($subName)) {
            $subName = $chartName;
        }
        $preloadedList = Report::selectRaw('event_date as e_date, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d')) - 293888;

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $count = $class::where($date, date("Y-m-d", (strtotime($thisDate) + 293888)))
                    ->where($where, $condition)
                    ->count();
                $outputObj[$thisDate] = $count;
                if ($i < $cutoffDate) {
                    $this->addReportData([
                        'event_date' => $thisDate,
                        'chart_key' => $chartName,
                        'sub_key' => $subName,
                        'event_count' => $count
                    ]);
                }
                unset($count);
            }
        }
        return $outputObj;
    }

    protected function getWinnerCount($startDate, $endDate, $class, $chartName, $rewardType, $reward, $where, $date = "created_date")
    {

        $subName = $chartName;
        $preloadedList = Report::selectRaw('event_date as e_date, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $count = $class::where($date, $thisDate)
                    ->where($where[0], $rewardType)
                    ->where($where[1], $reward)
                    ->count();
                $outputObj[$thisDate] = $count;
                if ($i < $cutoffDate) {
                    $this->addReportData([
                        'event_date' => $thisDate,
                        'chart_key' => $chartName,
                        'sub_key' => $subName,
                        'event_count' => $count
                    ]);
                }
                unset($count);
            }
        }
        return $outputObj;
    }

    protected function getRegisteredWhereCount($startDate, $endDate, $class, $chartName, $where, $condition, $like, $date = "created_date", $subName = null)
    {
        if (empty($subName)) {
            $subName = $chartName;
        }
        $preloadedList = Report::selectRaw('event_date as e_date, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $count = $class::where($date, $thisDate)
                    ->where("registered", 1)
                    ->where($where, $like, $condition)
                    ->count();
                $outputObj[$thisDate] = $count;
                if ($i < $cutoffDate) {
                    $this->addReportData([
                        'event_date' => $thisDate,
                        'chart_key' => $chartName,
                        'sub_key' => $subName,
                        'event_count' => $count
                    ]);
                }
                unset($count);
            }
        }
        return $outputObj;
    }

    protected function getRegistrationCount($startDate, $endDate)
    {
        $preloadedData = $this->getReportEventData('STATE_REGISTRATION', $startDate, $endDate);

        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $selectRows = "state ,count(state) as state_count";
                $registrationData = User::where('created_date', $thisDate)
                    ->where("registered", 1)
                    ->selectRaw($selectRows)->groupBy("state")->get();
                $createdHours = [];
                foreach ($registrationData as $info) {
                    $createdHours[$info->state] = $info->state_count;

                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => "STATE_REGISTRATION",
                            'sub_key' => $info->state,
                            'event_count' => $info->state_count
                        ]);
                    }
                }
                $outputObj[$thisDate] = $createdHours;
            }
        }

        $createdHourObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $createdHour => $count) {
                if (array_key_exists($createdHour, $createdHourObj)) {
                    $createdHourObj[$createdHour] += $count;
                } else {
                    $createdHourObj[$createdHour] = $count;
                }
            }
        }
        return $createdHourObj;
    }

    protected function getRepeatCount($startDate, $endDate)
    {
        $preloadedData = $this->getReportEventData("STATE_REPEAT", $startDate, $endDate);

        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $selectRows = "state ,count(state) as state_count";
                $registrationData = UserSession::where('created_date', $thisDate)
                    ->where("is_new_user", 0)
                    ->selectRaw($selectRows)->groupBy("state")->get();
                $createdHours = [];
                foreach ($registrationData as $info) {
                    $createdHours[$info->state] = $info->state_count;

                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => "STATE_REPEAT",
                            'sub_key' => $info->state,
                            'event_count' => $info->state_count
                        ]);
                    }
                }
                $outputObj[$thisDate] = $createdHours;
            }
        }

        $createdHourObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $createdHour => $count) {
                if (array_key_exists($createdHour, $createdHourObj)) {
                    $createdHourObj[$createdHour] += $count;
                } else {
                    $createdHourObj[$createdHour] = $count;
                }
            }
        }
        return $createdHourObj;
    }

    protected function getStateCount($startDate, $endDate, $class, $chart, $dateName)
    {
        $preloadedData = $this->getReportEventData($chart, $startDate, $endDate);

        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $selectRows = "state ,count(state) as state_count";
                $registrationData = $class::where($dateName, $thisDate)->selectRaw($selectRows)->groupBy("state")->get();
                $createdHours = [];
                foreach ($registrationData as $info) {
                    $createdHours[$info->state] = $info->state_count;

                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => $chart,
                            'sub_key' => $info->state,
                            'event_count' => $info->state_count
                        ]);
                    }
                }
                $outputObj[$thisDate] = $createdHours;
            }
        }

        $createdHourObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $createdHour => $count) {
                if (array_key_exists($createdHour, $createdHourObj)) {
                    $createdHourObj[$createdHour] += $count;
                } else {
                    $createdHourObj[$createdHour] = $count;
                }
            }
        }
        return $createdHourObj;
    }

    protected function getn3DStateWhereCount($startDate, $endDate, $class, $chart, $where, $value, $dateName = "created_date")
    {
        $preloadedData = $this->getReportEventData($chart, $startDate, $endDate);

        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $selectRows = "event_value ,count(event_value) as state_count";
                $registrationData = $class::where($dateName, date("Y-m-d", (strtotime($thisDate) + 293888)))
                    ->selectRaw($selectRows)
                    ->where($where, $value)
                    ->groupBy("event_value")->get();
                $createdHours = [];
                foreach ($registrationData as $info) {
                    $createdHours[$info->state] = $info->state_count;

                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => $chart,
                            'sub_key' => $info->event_value,
                            'event_count' => $info->state_count
                        ]);
                    }
                }
                $outputObj[$thisDate] = $createdHours;
            }
        }

        $createdHourObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $createdHour => $count) {
                if (array_key_exists($createdHour, $createdHourObj)) {
                    $createdHourObj[$createdHour] += $count;
                } else {
                    $createdHourObj[$createdHour] = $count;
                }
            }
        }
        return $createdHourObj;
    }

    protected function getStateDistributionData($startDate, $endDate)
    {
        $stateList = [];

        $stateRegistrationCount = $this->getRegistrationCount($startDate, $endDate);
        $repeat = $this->getRepeatCount($startDate, $endDate);
        $session = $this->getStateCount($startDate, $endDate, new UserSession(), "STATE_SESSION", "created_date");
        $mass6 = $this->getStateWinnerCount($startDate, $endDate, new Winner(), "STATE_WINNER_MASS_6", GenericConstant::$winTypeMassReward, GenericConstant::$winTypeMassReward6);
        $mass20 = $this->getStateWinnerCount($startDate, $endDate, new Winner(), "STATE_WINNER_MASS_20", GenericConstant::$winTypeMassReward, GenericConstant::$winTypeMassReward20);
        $mass50 = $this->getStateWinnerCount($startDate, $endDate, new Winner(), "STATE_WINNER_MASS_50", GenericConstant::$winTypeMassReward, GenericConstant::$winTypeMassReward50);
        $mass100 = $this->getStateWinnerCount($startDate, $endDate, new Winner(), "STATE_WINNER_MASS_100", GenericConstant::$winTypeMassReward, GenericConstant::$winTypeMassReward100);
        $merchWinner = $this->getStateWinnerCount($startDate, $endDate, new Winner(), "STATE_WINNER_MERCH", GenericConstant::$winTypeBumperReward, GenericConstant::$winTypeMerch);
        $ticketWinner = $this->getStateWinnerCount($startDate, $endDate, new Winner(), "STATE_WINNER_TICKET", GenericConstant::$winTypeBumperReward, GenericConstant::$winTypeTicket);
        $code = $this->getStateCount($startDate, $endDate, new UniqueCode(), "STATE_CODE", "created_date");
        $forfitWinner = $this->getn3DStateWhereCount($startDate, $endDate, new ClickTracker(), "STATE_FORFIET_WINNER", "event_type", GenericConstant::$eventBumperRewardForfeited, "created_date");

        foreach ($repeat as $state => $count) {
            if (!array_key_exists($state, $stateRegistrationCount)) {
                $stateRegistrationCount[$state] = 0;
            }
        }
        foreach ($session as $state => $count) {
            if (!array_key_exists($state, $stateRegistrationCount)) {
                $stateRegistrationCount[$state] = 0;
            }
        }
        foreach ($forfitWinner as $state => $count) {
            if (!array_key_exists($state, $stateRegistrationCount)) {
                $stateRegistrationCount[$state] = 0;
            }
        }
        foreach ($mass6 as $state => $count) {
            if (!array_key_exists($state, $stateRegistrationCount)) {
                $stateRegistrationCount[$state] = 0;
            }
        }
        foreach ($mass20 as $state => $count) {
            if (!array_key_exists($state, $stateRegistrationCount)) {
                $stateRegistrationCount[$state] = 0;
            }
        }
        foreach ($mass50 as $state => $count) {
            if (!array_key_exists($state, $stateRegistrationCount)) {
                $stateRegistrationCount[$state] = 0;
            }
        }
        foreach ($mass100 as $state => $count) {
            if (!array_key_exists($state, $stateRegistrationCount)) {
                $stateRegistrationCount[$state] = 0;
            }
        }
        foreach ($merchWinner as $state => $count) {
            if (!array_key_exists($state, $stateRegistrationCount)) {
                $stateRegistrationCount[$state] = 0;
            }
        }
        foreach ($ticketWinner as $state => $count) {
            if (!array_key_exists($state, $stateRegistrationCount)) {
                $stateRegistrationCount[$state] = 0;
            }
        }

        foreach ($code as $state => $count) {
            if (!array_key_exists($state, $stateRegistrationCount)) {
                $stateRegistrationCount[$state] = 0;
            }
        }

        ksort($stateRegistrationCount);

        foreach ($stateRegistrationCount as $state => $count) {
            $sessionCount = array_key_exists($state, $session) ?
                $session[$state] : 0;
            $repeatCount = array_key_exists($state, $repeat) ? $repeat[$state] : 0;
            $mass6Count = array_key_exists($state, $mass6) ? $mass6[$state] : 0;
            $mass20Count = array_key_exists($state, $mass20) ? $mass20[$state] : 0;
            $mass50Count = array_key_exists($state, $mass50) ? $mass50[$state] : 0;
            $mass100Count = array_key_exists($state, $mass100) ? $mass100[$state] : 0;
            $merchWinnerCount = array_key_exists($state, $merchWinner) ? $merchWinner[$state] : 0;
            $ticketWinnerCount = array_key_exists($state, $ticketWinner) ? $ticketWinner[$state] : 0;
            $forfitWinnerCount = array_key_exists($state, $forfitWinner) ? $forfitWinner[$state] : 0;
            $codeCount = array_key_exists($state, $code) ? $code[$state] : 0;
            if ($state == 'National Capital Territory of Delhi' || $state == "delhi - ncr") {
                $state = 'Delhi';
            }
            $stateList[] = [
                'state' => $state,
                'session' => $sessionCount,
                'codeCount' => $codeCount,
                'regCount' => $count,
                'repeatCount' => $repeatCount,
                'mass6Count' => $mass6Count,
                'mass20Count' => $mass20Count,
                'mass50Count' => $mass50Count,
                'mass100Count' => $mass100Count,
                'merchWinnerCount' => $merchWinnerCount,
                'ticketWinnerCount' => $ticketWinnerCount,
                "forfitWinner" => $forfitWinnerCount,
            ];
        }
        return $stateList;
    }

    protected function getStateWinnerCount($startDate, $endDate, $class, $chart, $reward, $rewardType, $dateName = "created_date")
    {
        $preloadedData = $this->getReportEventData($chart, $startDate, $endDate);

        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $selectRows = "state ,count(state) as state_count";
                $registrationData = $class::where($dateName, $thisDate)->where("reward_type", $reward)->where("reward_name", $rewardType)->selectRaw($selectRows)->groupBy("state")->get();
                $createdHours = [];
                foreach ($registrationData as $info) {
                    $createdHours[$info->state] = $info->state_count;

                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => $chart,
                            'sub_key' => $info->state,
                            'event_count' => $info->state_count
                        ]);
                    }
                }
                $outputObj[$thisDate] = $createdHours;
            }
        }

        $createdHourObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $createdHour => $count) {
                if (array_key_exists($createdHour, $createdHourObj)) {
                    $createdHourObj[$createdHour] += $count;
                } else {
                    $createdHourObj[$createdHour] = $count;
                }
            }
        }
        return $createdHourObj;
    }

    protected function getUniqueHavingCount($startDate, $endDate, $class, $chartName, $condition, $num)
    {
        $preloadedList = Report::selectRaw('event_date as e_date, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $count = $class::selectRaw("count(mobile) as count")
                    ->where('created_date', $thisDate)
                    ->groupBy("mobile")
                    ->where("registered", 1)
                    ->havingRaw("count(mobile) $condition ?", [$num])
                    ->get()
                    ->count();
                $outputObj[$thisDate] = $count;
                if ($i < $cutoffDate) {
                    $this->addReportData([
                        'event_date' => $thisDate,
                        'chart_key' => $chartName,
                        'sub_key' => $chartName,
                        'event_count' => $count
                    ]);
                }
                unset($count);
            }
        }
        return $outputObj;
    }

    protected function getDistinctCount($startDate, $endDate, $class, $chartName, $distinct, $subName = null)
    {
        if (empty($subName)) {
            $subName = $chartName;
        }
        $preloadedList = Report::selectRaw('event_date as e_date, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $query = $class::where('created_date', $thisDate);
                if (get_class($class) == "User") {
                    $query->where("registered", 1);
                }
                $count = $query->distinct($distinct)
                    ->count();
                $outputObj[$thisDate] = $count;
                if ($i < $cutoffDate) {
                    $this->addReportData([
                        'event_date' => $thisDate,
                        'chart_key' => $chartName,
                        'sub_key' => $subName,
                        'event_count' => $count
                    ]);
                }
                unset($count);
            }
        }
        return $outputObj;
    }

    protected function getWinnerCountState($startDate, $endDate, $data, $winType, $op = 'LIKE')
    {
        $preloadedList = Report::select('event_date', 'sub_key', 'event_count')
            ->where('chart_key', strtoupper($data) . '_WIN_' . $winType)
            ->where('event_date', '>=', $startDate)
            ->where('event_date', '<=', $endDate)
            ->get();

        $preloadedData = [];
        foreach ($preloadedList as $info) {
            if (!array_key_exists($info->event_date, $preloadedData)) {
                $preloadedData[$info->event_date] = [];
            }
            $preloadedData[$info->event_date][$info->sub_key] = $info->event_count;
        }

        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $dataName = "geo_$data";
                $dataCount = $data . "_count";
                $selectRows = "$dataName ,count($dataName) as $dataCount";
                $createdHourList = Winner::where('win_date', $thisDate)->selectRaw($selectRows)
                    ->where('reward_type', $op, $winType)
                    ->groupBy($dataName)->get();
                $createdHours = [];
                foreach ($createdHourList as $info) {
                    $createdHours[$info->$dataName] = $info->$dataCount;

                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => strtoupper($data) . '_WIN_' . $winType,
                            'sub_key' => $info->$dataName,
                            'event_count' => $info->$dataCount
                        ]);
                    }
                }
                $outputObj[$thisDate] = $createdHours;
            }
        }
        $createdHourObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $createdHour => $count) {
                if (array_key_exists($createdHour, $createdHourObj)) {
                    $createdHourObj[$createdHour] += $count;
                } else {
                    $createdHourObj[$createdHour] = $count;
                }
            }
        }
        return $createdHourObj;
    }

    protected function getCustomWhereCount($startDate, $endDate, $class, $chartName, $where, $condition, $customWhere, $subName = null)
    {
        if (empty($subName)) {
            $subName = $chartName;
        }
        $preloadedList = Report::selectRaw('event_date as e_date, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $count = $class::where('created_date', $thisDate)
                    ->where($where, $customWhere, $condition)
                    ->count();
                $outputObj[$thisDate] = $count;
                if ($i < $cutoffDate) {
                    $this->addReportData([
                        'event_date' => $thisDate,
                        'chart_key' => $chartName,
                        'sub_key' => $subName,
                        'event_count' => $count
                    ]);
                }
                unset($count);
            }
        }
        return $outputObj;
    }

    protected function getWhereNotNullCount($startDate, $endDate, $class, $chartName, $where, $subName = null)
    {
        if (empty($subName)) {
            $subName = $chartName;
        }
        $preloadedList = Report::selectRaw('event_date as e_date, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $count = $class::where('created_date', $thisDate)
                    ->whereNotNull($where)
                    ->count();
                $outputObj[$thisDate] = $count;
                if ($i < $cutoffDate) {
                    $this->addReportData([
                        'event_date' => $thisDate,
                        'chart_key' => $chartName,
                        'sub_key' => $subName,
                        'event_count' => $count
                    ]);
                }
                unset($count);
            }
        }
        return $outputObj;
    }

    protected function getCount($startDate, $endDate, $class, $chartName, $addRegister = false, $subName = null, $date = "created_date")
    {
        if (empty($subName)) {
            $subName = $chartName;
        }
        $preloadedList = Report::selectRaw('event_date as e_date, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $query = $class::where($date, $thisDate);
                if ($addRegister) {
                    $query->where("registered", 1);
                }
                $count = $query->count();
                $outputObj[$thisDate] = $count;
                if ($i < $cutoffDate) {
                    $this->addReportData([
                        'event_date' => $thisDate,
                        'chart_key' => $chartName,
                        'sub_key' => $subName,
                        'event_count' => $count
                    ]);
                }
                unset($count);
            }
        }
        return $outputObj;
    }

    protected function getSum($startDate, $endDate, $class, $chartName, $colName, $subName = null)
    {
        if (empty($subName)) {
            $subName = $chartName;
        }
        $preloadedList = Report::selectRaw('event_date as e_date, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $count = $class::where('created_date', $thisDate)
                    ->sum($colName);
                $outputObj[$thisDate] = $count;
                if ($i < $cutoffDate) {
                    $this->addReportData([
                        'event_date' => $thisDate,
                        'chart_key' => $chartName,
                        'sub_key' => $subName,
                        'event_count' => $count
                    ]);
                }
                unset($count);
            }
        }
        return $outputObj;
    }

    protected function getGroupByCount($startDate, $endDate, $class, $chartName, $groupBy)
    {
        $preloadedList = Report::selectRaw('event_date as e_date,sub_key, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->groupBy('sub_key')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date][$info->sub_key] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                unset($count);
                $count = $class::selectRaw("count(registration_source) as count,registration_source")
                    ->where('created_date', $thisDate)
                    ->groupBy($groupBy)
                    ->get();
                foreach ($count as $value) {
                    $createdDays[$value->registration_source] = $value->count;
                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => $chartName,
                            'sub_key' => $value->registration_source,
                            'event_count' => $value->count
                        ]);
                    }
                }
                if (empty($createdDays)) {
                    continue;
                }
                $outputObj[$thisDate] = $createdDays;
                unset($count);
                unset($createdDays);
            }
        }
        $createdDayObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $createdDay => $count) {
                if (array_key_exists($createdDay, $createdDayObj)) {
                    $createdDayObj[$createdDay] += $count;
                } else {
                    $createdDayObj[$createdDay] = $count;
                }
            }
        }
        $response = [];
        foreach ($createdDayObj as $createdDay => $count) {
            $response[] = (object)[
                "traffic" => $createdDay,
                "count" => $count
            ];
        }
        return $response;
    }

    protected function getUniqueGroupByCount($startDate, $endDate, $class, $chartName, $source = "last_source")
    {
        $preloadedList = Report::selectRaw('event_date as e_date,sub_key, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->groupBy('sub_key')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date][$info->sub_key] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                unset($count);
                $count = $class::selectRaw("count(*) as count,$source")
                    ->where('created_date', $thisDate)
                    ->where("first_session_of_day", 1)
                    ->groupBy($source)
                    ->get();
                foreach ($count as $value) {
                    $createdDays[$value->$source] = $value->count;
                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => $chartName,
                            'sub_key' => $value->$source,
                            'event_count' => $value->count
                        ]);
                    }
                }
                if (empty($createdDays)) {
                    continue;
                }
                $outputObj[$thisDate] = $createdDays;
                unset($count);
                unset($createdDays);
            }
        }
        $createdDayObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $createdDay => $count) {
                if (array_key_exists($createdDay, $createdDayObj)) {
                    $createdDayObj[$createdDay] += $count;
                } else {
                    $createdDayObj[$createdDay] = $count;
                }
            }
        }
        return $createdDayObj;
    }

    private function getReportEventData($chartKey, $startDate, $endDate)
    {
        $preloadedList = Report::select('event_date', 'sub_key', 'event_count')
            ->where('chart_key', $chartKey)
            ->where('event_date', '>=', $startDate)
            ->where('event_date', '<=', $endDate)
            ->get();

        $preloadedData = [];
        foreach ($preloadedList as $info) {
            if (!array_key_exists($info->event_date, $preloadedData)) {
                $preloadedData[$info->event_date] = [];
            }
            $preloadedData[$info->event_date][$info->sub_key] = $info->event_count;
        }
        return $preloadedData;
    }

    protected function getTrafficGroupByCount($startDate, $endDate, $class, $chartName, $source = "last_source")
    {
        $preloadedList = Report::selectRaw('event_date as e_date,sub_key, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->groupBy('sub_key')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date][$info->sub_key] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                unset($count);
                $count = $class::selectRaw("count(*) as count,$source")
                    ->where('created_date', $thisDate)
                    ->groupBy("$source")
                    ->get();
                foreach ($count as $value) {
                    $createdDays[$value->$source] = $value->count;
                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => $chartName,
                            'sub_key' => $value->$source,
                            'event_count' => $value->count
                        ]);
                    }
                }
                if (empty($createdDays)) {
                    continue;
                }
                $outputObj[$thisDate] = $createdDays;
                unset($count);
                unset($createdDays);
            }
        }
        $createdDayObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $createdDay => $count) {
                if (array_key_exists($createdDay, $createdDayObj)) {
                    $createdDayObj[$createdDay] += $count;
                } else {
                    $createdDayObj[$createdDay] = $count;
                }
            }
        }
        return $createdDayObj;
    }

    protected function getRegGroupByCount($startDate, $endDate, $class, $chartName, $source = "last_source", $created_date = "created_date")
    {
        $preloadedList = Report::selectRaw('event_date as e_date,sub_key, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->groupBy('sub_key')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date][$info->sub_key] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                unset($count);
                $count = $class::selectRaw("count(*) as count,$source")
                    ->where('created_date', $thisDate)
                    ->where("registered", 1)
                    ->groupBy("$source")
                    ->get();
                foreach ($count as $value) {
                    $createdDays[$value->$source] = $value->count;
                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => $chartName,
                            'sub_key' => $value->$source,
                            'event_count' => $value->count
                        ]);
                    }
                }
                if (empty($createdDays)) {
                    continue;
                }
                $outputObj[$thisDate] = $createdDays;
                unset($count);
                unset($createdDays);
            }
        }
        $createdDayObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $createdDay => $count) {
                if (array_key_exists($createdDay, $createdDayObj)) {
                    $createdDayObj[$createdDay] += $count;
                } else {
                    $createdDayObj[$createdDay] = $count;
                }
            }
        }
        return $createdDayObj;
    }
    protected function getTrafficCount($startDate, $endDate, $class, $chartName, $where, $num, $source = "last_source")
    {
        $preloadedList = Report::selectRaw('event_date as e_date,sub_key, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->groupBy('sub_key')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date][$info->sub_key] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                unset($count);
                $count = $class::selectRaw("count(*) as count,$source")
                    ->where('created_date', $thisDate)
                    ->where($where, $num)
                    ->groupBy("$source")
                    ->get();
                foreach ($count as $value) {
                    $createdDays[$value->$source] = $value->count;
                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => $chartName,
                            'sub_key' => $value->$source,
                            'event_count' => $value->count
                        ]);
                    }
                }
                if (empty($createdDays)) {
                    continue;
                }
                $outputObj[$thisDate] = $createdDays;
                unset($count);
                unset($createdDays);
            }
        }
        $createdDayObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $createdDay => $count) {
                if (array_key_exists($createdDay, $createdDayObj)) {
                    $createdDayObj[$createdDay] += $count;
                } else {
                    $createdDayObj[$createdDay] = $count;
                }
            }
        }
        return $createdDayObj;
    }
    protected function getTrafficOnlyCount($startDate, $endDate, $class, $chartName, $source = "last_source")
    {
        $preloadedList = Report::selectRaw('event_date as e_date,sub_key, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->groupBy('sub_key')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date][$info->sub_key] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                unset($count);
                $count = $class::selectRaw("count(*) as count,$source")
                    ->where('created_date', $thisDate)
                    ->groupBy("$source")
                    ->get();
                foreach ($count as $value) {
                    $createdDays[$value->$source] = $value->count;
                    if ($i < $cutoffDate) {
                        $this->addReportData([
                            'event_date' => $thisDate,
                            'chart_key' => $chartName,
                            'sub_key' => $value->$source,
                            'event_count' => $value->count
                        ]);
                    }
                }
                if (empty($createdDays)) {
                    continue;
                }
                $outputObj[$thisDate] = $createdDays;
                unset($count);
                unset($createdDays);
            }
        }
        $createdDayObj = [];
        foreach ($outputObj as $output) {
            foreach ($output as $createdDay => $count) {
                if (array_key_exists($createdDay, $createdDayObj)) {
                    $createdDayObj[$createdDay] += $count;
                } else {
                    $createdDayObj[$createdDay] = $count;
                }
            }
        }
        return $createdDayObj;
    }

    protected function getCountConditions($startDate, $endDate, $class, $chartName, $conditions)
    {

        $preloadedList = Report::selectRaw('event_date as e_date, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $count = $class::where('created_date', $thisDate)
                    ->where($conditions)
                    ->count();
                $outputObj[$thisDate] = $count;
                if ($i < $cutoffDate) {
                    $this->addReportData([
                        'event_date' => $thisDate,
                        'chart_key' => $chartName,
                        'sub_key' => $chartName,
                        'event_count' => $count
                    ]);
                }
                unset($count);
            }
        }
        return $outputObj;
    }

    protected function getDailyLimitOverCount($startDate, $endDate, $chartName)
    {

        $preloadedList = Report::selectRaw('event_date as e_date, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $count = DailyCodeCount::where('created_date', $thisDate)
                    ->where('valid_code_count', '>', 5)
                    ->orWhere('invalid_code_count', '>=', 5)
                    ->count();
                $outputObj[$thisDate] = $count;
                if ($i < $cutoffDate) {
                    $this->addReportData([
                        'event_date' => $thisDate,
                        'chart_key' => $chartName,
                        'sub_key' => $chartName,
                        'event_count' => $count
                    ]);
                }
                unset($count);
            }
        }
        return $outputObj;
    }

    protected function getClaimNudgeCount($startDate, $endDate, $chartName)
    {

        $preloadedList = Report::selectRaw('event_date as e_date, SUM(event_count) as e_count')
            ->where('chart_key', $chartName)
            ->groupBy('e_date')
            ->orderBy('e_date')
            ->get();
        $preloadedData = [];
        foreach ($preloadedList as $info) {
            $preloadedData[$info->e_date] = $info->e_count;
        }
        $startTime = strtotime($startDate);
        $endTime = strtotime($endDate);
        $outputObj = [];
        $cutoffDate = strtotime(date('Y-m-d'));

        for ($i = $startTime; $i <= $endTime; $i = $i + 86400) {
            $thisDate = date('Y-m-d', $i);
            if (array_key_exists($thisDate, $preloadedData)) {
                $outputObj[$thisDate] = $preloadedData[$thisDate];
            } else {
                $count = FlowToken::where('created_date', $thisDate)
                    ->where('flow_token_type', FlowConstant::$typeClaimForm . '_0')
                    ->orWhere('flow_token_type', FlowConstant::$typeClaimForm . '_1')
                    ->orWhere('flow_token_type', FlowConstant::$typeClaimForm . '_2')
                    ->count();
                $outputObj[$thisDate] = $count;
                if ($i < $cutoffDate) {
                    $this->addReportData([
                        'event_date' => $thisDate,
                        'chart_key' => $chartName,
                        'sub_key' => $chartName,
                        'event_count' => $count
                    ]);
                }
                unset($count);
            }
        }
        return $outputObj;
    }

    private function addReportData($saveData)
    {
        try {
            Report::saveData($saveData);
        } catch (QueryException) {
            return false;
        }
        return true;
    }

    protected function getDateWiseCount($data)
    {
        $count = 0;
        foreach ($data as $eachCount) {
            $count += $eachCount;
        }
        return $count;
    }
}
