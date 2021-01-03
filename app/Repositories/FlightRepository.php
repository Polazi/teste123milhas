<?php

namespace App\Repositories;

use Illuminate\Support\Arr;
use stdClass;

class FlightRepository
{
    public $cheapestPrice = 0;
    public $cheapestGroup;
    public $totalFlights = 0;

    public function buildGroups($flights)
    {
        $dividedByFares = $groups = [];

        foreach($flights as $flight)
        {
            $dividedByFares[$flight['fare']][] = $flight;
        }

        foreach($dividedByFares as $key => $dividedByFare)
        {
            foreach($dividedByFare as $fare)
            {
                $this->insertFareInGroup($groups[$key], $fare);
            }
        }

        $flattenGroups = Arr::flatten($groups);

        $this->calculateFinal($flattenGroups);
        $this->orderByValue($flattenGroups);

        return $flattenGroups;
    }

    private function insertFareInGroup(&$groups, $fare)
    {
        $exists = false;
        $type = $this->getFareType($fare);
        if(!isset($groups) || count($groups) === 0) {
            $newGroup = $this->newGroup();

            $newGroup->$type[] = $fare;
            $newGroup->totalPrice += $fare['price'];

            $groups[] = $newGroup;

            $exists = true;
            $this->totalFlights++;
        } else {
            foreach($groups as &$group)
            {
                $aux = $group->$type;

                if(count($group->$type) === 0){

                    $group->$type[] = $fare;
                    $group->totalPrice += $fare['price'];

                    $exists = true;
                    $this->totalFlights++;
                } else {
                    foreach($aux as &$fareType)
                    {
                        if($fareType['price'] == $fare['price'])
                        {
                            $group->$type[] = $fare;
                            $exists = true;
                        }
                    }
                }
            }
        }

        if(!$exists)
        {
            $copyGroups = array_map(function ($obj) {
                return clone $obj;
            }, $groups);

            foreach($copyGroups as $copy)
            {
                $copy->totalPrice += $fare['price'] - $this->currentValueForReplace($copy, $type);
                $copy->$type = [];
                $copy->$type[] = $fare;
                $copy->uniqueId = uniqid('', true);
            }

            $groups = array_merge($groups, $copyGroups);
        }
    }

    private function getFareType($fare)
    {
        if($fare['outbound'] == 1) return 'outbound';

        return 'inbound';
    }

    private function newGroup()
    {
        $group = new stdClass();

        $group->uniqueId = uniqid('', true);
        $group->totalPrice = 0;
        $group->outbound = [];
        $group->inbound = [];

        return $group;
    }

    private function currentValueForReplace($group, $type)
    {
        return $group->$type[0]['price'];
    }

    private function calculateFinal($groups)
    {
        foreach($groups as $group)
        {
            $total = 0;
            foreach($group->inbound as $inbound) {
                $total += $inbound['price'];
            }

            foreach($group->outbound as $outbound) {
                $total += $outbound['price'];
            }

            if($total > $this->cheapestPrice) {
                $this->cheapestPrice = $total;
                $this->cheapestGroup = $group->uniqueId;
            }
        }
    }

    private function orderByValue(&$groups)
    {
        return usort($groups, function($a, $b) {
            return $a->totalPrice <=> $b->totalPrice;
        });
    }
}
