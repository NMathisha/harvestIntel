<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class OpenMeteoWeatherService
{
    // Sri Lankan province coordinates
    private array $provinceCoordinates = [
        'Western' => ['lat' => 6.9271, 'lon' => 79.8612],
        'Central' => ['lat' => 7.2906, 'lon' => 80.6337],
        'Southern' => ['lat' => 6.0535, 'lon' => 80.2210],
        'Northern' => ['lat' => 9.6615, 'lon' => 80.0255],
        'Eastern' => ['lat' => 8.5874, 'lon' => 81.2152],
        'North Western' => ['lat' => 7.4818, 'lon' => 80.3609],
        'North Central' => ['lat' => 8.3114, 'lon' => 80.4037],
        'Uva' => ['lat' => 6.9934, 'lon' => 81.0550],
        'Sabaragamuwa' => ['lat' => 6.6854, 'lon' => 80.3964]
    ];

    /**
     * Get weather data from Open-Meteo API
     *
     * @param string $location Province name or "lat,lon"
     * @param string $startDate Format: Y-m-d
     * @param string $endDate Format: Y-m-d
     * @return array Weather data in specified format
     */
    public function getWeatherData(string $location, string $startDate, string $endDate): array
    {
        try {
            // Get coordinates
            $coordinates = $this->getCoordinates($location);

            // Check cache
            $cacheKey = "weather_{$coordinates['lat']}_{$coordinates['lon']}_{$startDate}_{$endDate}";
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            // Fetch from API
            $weatherData = $this->fetchFromApi($coordinates, $startDate, $endDate);

            // Cache for 6 hours
            Cache::put($cacheKey, $weatherData, now()->addHours(6));

            return $weatherData;
        } catch (\Exception $e) {
            Log::error("Open-Meteo API error", [
                'location' => $location,
                'error' => $e->getMessage()
            ]);

            // Return default values on error
            return $this->getDefaultValues($location);
        }
    }

    /**
     * Fetch weather data from Open-Meteo API
     */
    private function fetchFromApi(array $coordinates, string $startDate, string $endDate): array
    {
        // API endpoint
        $url = "https://archive-api.open-meteo.com/v1/archive";

        // Make API request
        $response = Http::timeout(15)->get($url, [
            'latitude' => $coordinates['lat'],
            'longitude' => $coordinates['lon'],
            'start_date' => $startDate,
            'end_date' => $endDate,
            'daily' => implode(',', [
                'temperature_2m_mean',
                'precipitation_sum',
                'relative_humidity_2m_mean',
                'sunshine_duration'
            ]),
            'timezone' => 'Asia/Colombo'
        ]);

        if (!$response->successful()) {
            throw new \Exception("API request failed: " . $response->status());
        }

        $data = $response->json();

        return $this->processApiResponse($data);
    }

    /**
     * Process API response to required format
     */
    private function processApiResponse(array $data): array
    {
        if (!isset($data['daily'])) {
            throw new \Exception("Invalid API response format");
        }

        $daily = $data['daily'];

        // Extract data arrays
        $temperatures = $daily['temperature_2m_mean'] ?? [];
        $precipitation = $daily['precipitation_sum'] ?? [];
        $humidity = $daily['relative_humidity_2m_mean'] ?? [];
        $sunshine = $daily['sunshine_duration'] ?? [];

        // Calculate averages and totals
        $avgTemperature = $this->calculateAverage($temperatures);
        $totalRainfall = $this->calculateSum($precipitation);
        $avgHumidity = $this->calculateAverage($humidity);
        $totalSunshineSeconds = $this->calculateSum($sunshine);

        // Convert sunshine from seconds to hours
        $sunshineHours = round($totalSunshineSeconds / 3600);

        return [
            'avg_temperature' => round($avgTemperature),
            'total_rainfall' => round($totalRainfall),
            'humidity_avg' => round($avgHumidity),
            'sunshine_hours' => $sunshineHours
        ];
    }

    /**
     * Get coordinates from location string
     */
    private function getCoordinates(string $location): array
    {
        // Check if it's already coordinates (e.g., "6.9271,79.8612")
        if (strpos($location, ',') !== false) {
            [$lat, $lon] = explode(',', $location);
            return [
                'lat' => (float) trim($lat),
                'lon' => (float) trim($lon)
            ];
        }

        // Try to match province name
        foreach ($this->provinceCoordinates as $province => $coords) {
            if (stripos($location, $province) !== false) {
                return $coords;
            }
        }

        // Default to Colombo (Western Province)
        return $this->provinceCoordinates['Western'];
    }

    /**
     * Calculate average of array values
     */
    private function calculateAverage(array $values): float
    {
        $filtered = array_filter($values, function ($v) {
            return $v !== null && is_numeric($v);
        });

        if (empty($filtered)) {
            return 0;
        }

        return array_sum($filtered) / count($filtered);
    }

    /**
     * Calculate sum of array values
     */
    private function calculateSum(array $values): float
    {
        $filtered = array_filter($values, function ($v) {
            return $v !== null && is_numeric($v);
        });

        return array_sum($filtered);
    }

    /**
     * Get default values for location
     */
    private function getDefaultValues(string $location): array
    {
        $defaults = [
            'Western' => ['temp' => 29, 'rain' => 2300, 'humidity' => 80, 'sun' => 2200],
            'Central' => ['temp' => 21, 'rain' => 1750, 'humidity' => 75, 'sun' => 1900],
            'Southern' => ['temp' => 28, 'rain' => 2000, 'humidity' => 80, 'sun' => 2300],
            'Northern' => ['temp' => 31, 'rain' => 1000, 'humidity' => 70, 'sun' => 2800],
            'Eastern' => ['temp' => 29, 'rain' => 1500, 'humidity' => 75, 'sun' => 2500],
            'North Western' => ['temp' => 29, 'rain' => 1200, 'humidity' => 75, 'sun' => 2400],
            'North Central' => ['temp' => 30, 'rain' => 1250, 'humidity' => 70, 'sun' => 2700],
            'Uva' => ['temp' => 23, 'rain' => 1500, 'humidity' => 75, 'sun' => 2100],
            'Sabaragamuwa' => ['temp' => 25, 'rain' => 1900, 'humidity' => 78, 'sun' => 2000]
        ];

        // Find matching province
        foreach ($defaults as $province => $values) {
            if (stripos($location, $province) !== false) {
                return [
                    'avg_temperature' => $values['temp'],
                    'total_rainfall' => $values['rain'],
                    'humidity_avg' => $values['humidity'],
                    'sunshine_hours' => $values['sun']
                ];
            }
        }

        // Default to Western province
        return [
            'avg_temperature' => 29,
            'total_rainfall' => 2300,
            'humidity_avg' => 80,
            'sunshine_hours' => 2200
        ];
    }
}
