# Real-time Location Updates

## Overview

According to the specification, real-time location updates for trucks should be implemented using WebSockets or Server-Sent Events (SSE).

## Implementation Options

### Option 1: Laravel Broadcasting with Pusher/Soketi

1. **Install Laravel Echo and Pusher JS** (or use Soketi for self-hosted):
   ```bash
   npm install --save-dev laravel-echo pusher-js
   ```

2. **Configure Broadcasting** in `config/broadcasting.php`

3. **Create Event** for truck location updates:
   ```php
   php artisan make:event TruckLocationUpdated
   ```

4. **Broadcast Event** when truck location is updated in `TruckController::updateLocation()`

### Option 2: Server-Sent Events (SSE)

1. **Create SSE Endpoint** in `routes/api.php`:
   ```php
   Route::get('/trucks/{id}/location-stream', [TruckController::class, 'locationStream']);
   ```

2. **Implement SSE Response** in `TruckController::locationStream()`

### Option 3: WebSocket with Laravel Reverb (Laravel 11+)

1. **Install Laravel Reverb**:
   ```bash
   composer require laravel/reverb
   php artisan reverb:install
   ```

2. **Start Reverb Server**:
   ```bash
   php artisan reverb:start
   ```

3. **Broadcast Events** when truck location updates

## Current Implementation

Currently, truck location updates are stored in the database via `PUT /api/trucks/{id}/location` endpoint.

To enable real-time updates, implement one of the above options and broadcast location changes when `TruckController::updateLocation()` is called.

## Example Implementation (SSE)

```php
public function locationStream(Request $request, $id)
{
    return response()->stream(function () use ($id) {
        $truck = Truck::findOrFail($id);
        
        while (true) {
            $truck->refresh();
            
            $data = json_encode([
                'id' => $truck->id,
                'latitude' => $truck->current_latitude,
                'longitude' => $truck->current_longitude,
                'timestamp' => $truck->last_location_update?->toIso8601String(),
            ]);
            
            echo "data: {$data}\n\n";
            ob_flush();
            flush();
            
            sleep(5); // Update every 5 seconds
        }
    }, 200, [
        'Content-Type' => 'text/event-stream',
        'Cache-Control' => 'no-cache',
        'Connection' => 'keep-alive',
    ]);
}
```

