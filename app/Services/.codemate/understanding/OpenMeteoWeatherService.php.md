The `OpenMeteoWeatherService` class provides weather data retrieval functionality for Sri Lankan provinces or specific latitude/longitude coordinates using the Open-Meteo API. Key features include:

- **Province Coordinates:** Predefined latitude and longitude for Sri Lankan provinces.
- **Weather Data Retrieval:** The `getWeatherData` method accepts a location (province name or "lat,lon") and a date range, returning average temperature, total rainfall, average humidity, and sunshine hours.
- **Caching:** Weather data responses are cached for 6 hours to reduce API calls.
- **API Integration:** Fetches historical weather data from the Open-Meteo archive API, requesting daily mean temperature, precipitation sum, relative humidity, and sunshine duration.
- **Data Processing:** Processes API responses to compute averages and totals, converting sunshine duration from seconds to hours.
- **Error Handling:** Logs errors and returns default weather values for provinces if the API call fails or data is invalid.
- **Utility Methods:** Includes helper functions to parse coordinates from input, calculate averages and sums, and provide default weather values per province.