<?php
/**
 * Created by PhpStorm.
 * User: orestis
 * Date: 5/5/2018
 * Time: 1:22 μμ
 */

namespace App\Services;

define('GOOGLE_DEST_URL', 'https://maps.googleapis.com/maps/api/distancematrix/json?');

class DistanceCalculator
{
    public function ddd($lat_route, $lon_route, $des_lat, $des_lon){
        $matrix[41.092083][23.541016][41.068238][23.390686]=10000;
        return $matrix[$lat_route][$lon_route][$des_lat][$des_lon];
    }

    public function calculateDistance($lat_route, $lon_route, $des_lat, $des_lon)
    {
        $distance = $this->setupUrl($lat_route, $lon_route, $des_lat, $des_lon);
        $distanceDec = json_decode($distance);

        if ($distanceDec->status != "OK") {
            dd($distance);
        }

        $result = $this->formatResult($distanceDec->rows);

        return $result;
    }

    private function curlGoogleDistanceApi($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result;
    }

    private function setupUrl($lat_route, $lon_route, $des_lat, $des_lon)
    {
        $url = GOOGLE_DEST_URL . "origins=$lat_route,$lon_route&destinations=$des_lat,$des_lon&key=AIzaSyAGrQY2qHDggcQI9Htkw8oDsBwbmchBkig";

        $result = $this->curlGoogleDistanceApi($url);
        return $result;
    }

    private function formatResult($distance)
    {
        $parseDist = $distance[0]->elements[0]->distance;

        $distArr = [
            'km_distance' => $parseDist->text,
            'meters_distance' => $parseDist->value
        ];

        return $distArr;
    }
}