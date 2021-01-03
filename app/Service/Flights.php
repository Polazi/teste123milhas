<?php

namespace App\Service;

use Illuminate\Support\Facades\Http;

class Flights
{
    private $url = 'http://prova.123milhas.net/api/flights';

    public function getFlights()
    {
        $response = Http::get($this->url, [
            'outbound' => request()->outbound
        ]);

        if($response->successful()) return $response->json();


        throw new \Exception('Houve um erro ao requisitar dados na API');
    }
}
