<?php

namespace App\Http\Controllers;

use App\Services\AlgorithmBuilder;
use App\Services\AlgorithmExecutor;
use App\Services\DistanceCalculator;
use App\Services\VillageSchematics;
use App\Village;
use App\VillageConnection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class GeneralController extends Controller
{
    private $dataFile;

    /**
     * GeneralController constructor.
     */
    public function __construct()
    {
        $this->dataFile = file_get_contents('files/b1.txt');
    }

    public function calculate(Request $request)
    {
        //convert json to object
        //calculus
        //response with object
    }

    public function index()
    {
        $village = Village::all();
        $combinations = VillageConnection::all();
        $tmpCombinations = $combinations->toArray();

        $villageConn = [];
        $i = 1;
        foreach ($combinations as $combination) {
            $needle = [
                "id" => $i,
                "route_village" => $combination->route_village,
                "connected_village" => $combination->connected_village
            ];
            $i++;//maybe
            array_unshift($tmpCombinations, [0, 0, 0]);
            if (array_search($needle, $tmpCombinations)) {
                $villageConn[] = $combination;
            }
            unset($tmpCombinations[0]);

            foreach ($tmpCombinations as $key => $tmp) {
                if ($tmp['route_village'] === $combination->connected_village && $tmp['connected_village'] === $combination->route_village) {
                    unset($tmpCombinations[$key]);
                }
            }

        }

        foreach ($villageConn as $key => $value) {
            $route = DB::select(DB::raw(
                'SELECT latitude, longitude FROM villages WHERE id = ' . $value->route_village
            ));

            $dest = DB::select(DB::raw(
                'SELECT latitude, longitude FROM villages WHERE id = ' . $value->connected_village
            ));

            $villageConn[$key]['route_coord'] = $route;
            $villageConn[$key]['dest_coord'] = $dest;
        }

        return view('pages.index')->withVillage($village)->withVillageConn($villageConn);
    }

    public function distanceCalculator(Request $request)
    {
        if ($request->input['hasData']) {
            //calc for those data
        } else {
            $distance = new DistanceCalculator();

            $combinations = DB::select(DB::raw(
                'SELECT vil.name as route, vill.name as dest FROM TrashDisposalSystem.connections as conn
                      INNER JOIN TrashDisposalSystem.villages as vil ON vil.id = conn.route_village
                      INNER JOIN TrashDisposalSystem.villages as vill ON vill.id = conn.connected_village;'
            ));

            $combinationArr = [];
            foreach ($combinations as $combination) {
                $revert = (object)[
                    'route' => $combination->dest,
                    'dest' => $combination->route
                ];

                if (array_search($revert, $combinationArr) === false) {
                    $combinationArr[] = $combination;
                }

            }

            $calculated = [];
            foreach ($combinationArr as $key => $value) {
                $coordinates = [];
                foreach ($value as $v) {
                    $coordinates[$v] = DB::table('villages')
                        ->select('latitude', 'longitude')
                        ->where('name', $v)
                        ->first();
                }

                foreach ($value as $path => $vil) {
                    if ($path == 'route') {
                        $route_lat = $coordinates[$vil]->latitude;
                        $route_lon = $coordinates[$vil]->longitude;
                    } else {
                        $des_lat = $coordinates[$vil]->latitude;
                        $des_lon = $coordinates[$vil]->longitude;
                    }
                }

                $calc = $distance->calculateDistance($route_lat, $route_lon, $des_lat, $des_lon);
                $calculated[] = [
                    'from' => $value->route,
                    'to' => $value->dest,
                    'distance_km' => $calc['km_distance'],
                    'distance_m' => $calc['meters_distance']
                ];
            }
        }

        return $calculated;
    }

    public function demo(Request $request)
    {
        $results = [];
        for ($i = 0; $i < 15; $i++) {
            $villagesArray = $this->dataFile;
            $villages = array();
            $decVil = json_decode($villagesArray);
            foreach ($decVil as $village) {
                $villageSch = VillageSchematics::parse($village);
                array_push($villages, $villageSch);
            }
            $algorithmExecutor = new AlgorithmExecutor(new AlgorithmBuilder());

            collect($villages)->each(function ($vil) use ($algorithmExecutor) {
                $algorithmExecutor->addVillage($vil);
            });
            $val = $algorithmExecutor->execute();
            $toTest = explode(":", $val['path']);
            
            if (!str_contains($toTest[count($toTest) - 2], 'Adelfiko'))
                continue;

            array_push($results, $val);
        }

        $minDistance = collect($results)->min(function ($v) {
            return $v['distance'];
        });

        return $this->locateItem($results, $minDistance);
    }

    public function questionB2()
    {
        $results = [];
        for ($i = 0; $i < 15; $i++) {
            $villagesArray = $this->dataFile;
            $villages = array();
            $decVil = json_decode($villagesArray);
            $villages = array();
            foreach ($decVil as $village) {
                $villageSch = VillageSchematics::parse($village);
                array_push($villages, $villageSch);
            }
            $algorithmExecutor = new AlgorithmExecutor(new AlgorithmBuilder());
            collect($villages)->each(function ($vil) use ($algorithmExecutor) {
                $algorithmExecutor->addVillage($vil);
            });

            $algorithmExecutor->lastNodeOneTimeOnlyStatus(true);
            array_push($results, $algorithmExecutor->execute());
        }

        $minDistance = collect($results)->min(function ($v) {
            return $v['distance'];
        });

        return $this->locateItem($results, $minDistance);
    }

    function locateItem($map, $value)
    {
        foreach ($map as $item)
            if ($item['distance'] === $value)
                return $item;

        echo "error";
        Log::info("error on locate item");
        return $map[0];
    }

    public function questionB3()
    {
        $villagesArray = $this->dataFile;
        $villages = array();
        $decVil = json_decode($villagesArray);
        $villages = array();
        foreach ($decVil as $village) {
            $villageSch = VillageSchematics::parse($village);
            array_push($villages, $villageSch);
        }
        $algorithmExecutor = new AlgorithmExecutor(new AlgorithmBuilder());
        collect($villages)->each(function ($vil) use ($algorithmExecutor) {
            $algorithmExecutor->addVillage($vil);
        });

        $algorithmExecutor->lastNodeOneTimeOnlyStatus(true);
        $algorithmExecutor->specificAreasOnly(true);

        return $algorithmExecutor->execute();
    }

    public function questionB4()
    {
        $villagesArray = $this->dataFile;
        $villages = array();
        $decVil = json_decode($villagesArray);
        $villages = array();
        foreach ($decVil as $village) {
            $villageSch = VillageSchematics::parse($village);
            array_push($villages, $villageSch);
        }
        $algorithmExecutor = new AlgorithmExecutor(new AlgorithmBuilder());
        collect($villages)->each(function ($vil) use ($algorithmExecutor) {
            $algorithmExecutor->addVillage($vil);
        });

        $algorithmExecutor->lastNodeOneTimeOnlyStatus(true);
        $algorithmExecutor->specificAreasOnly(true);
        $algorithmExecutor->specificAreasOnlyAndInclusive(true);

        return $algorithmExecutor->execute();
    }

    public function questionB5()
    {
        $villagesArray = $this->dataFile;
        $villages = array();
        $decVil = json_decode($villagesArray);
        $villages = array();
        foreach ($decVil as $village) {
            $villageSch = VillageSchematics::parse($village);
            $villageSch->setSize(100);
            array_push($villages, $villageSch);
        }
        $algorithmExecutor = new AlgorithmExecutor(new AlgorithmBuilder());
        collect($villages)->each(function ($vil) use ($algorithmExecutor) {
            $algorithmExecutor->addVillage($vil);
        });

        $algorithmExecutor->lastNodeOneTimeOnlyStatus(true);
        $algorithmExecutor->twoTracks(true);

        return $algorithmExecutor->execute();
    }
}