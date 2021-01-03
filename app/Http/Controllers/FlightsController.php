<?php

namespace App\Http\Controllers;

use App\Repositories\FlightRepository;
use App\Service\Flights;
use Illuminate\Http\Request;

class FlightsController extends Controller
{
    private $service, $repo;

    public function __construct(Flights $service, FlightRepository $repo)
    {
        $this->service = $service;
        $this->repo = $repo;
    }

    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(Request $request)
    {
        try {
            $flightsData = $this->service->getFlights();

            $groups = $this->repo->buildGroups($flightsData);
        } catch(\Exception $e){
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }

        return response()->json([
            "flights" => $flightsData,
            "groups" => $groups,
            "totalGroups" => count($groups),
            "totalFlights" => $this->repo->totalFlights,
            "cheapestPrice" => $this->repo->cheapestPrice,
            "cheapestGroup" => $this->repo->cheapestGroup
        ], 200);
    }
}
