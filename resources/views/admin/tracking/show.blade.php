<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Tracking - Ride {{ $booking->booking_number }}</title>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <style>
        body, html {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }
        #map {
            height: 100%;
            width: 100%;
        }
        .header {
            position: absolute;
            top: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: white;
            padding: 10px 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
            z-index: 1000;
            text-align: center;
        }
        .status {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
    </style>
</head>
<body>

    <div class="header">
        <strong>Ride {{ $booking->booking_number }}</strong>
        <div class="status" id="status-text">Connecting...</div>
    </div>

    <div id="map"></div>

    <!-- Leaflet JS -->
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Default center if no coordinates yet (India roughly)
            var defaultLat = {{ $booking->current_latitude ?? 20.5937 }};
            var defaultLng = {{ $booking->current_longitude ?? 78.9629 }};
            
            var map = L.map('map').setView([defaultLat, defaultLng], {{ $booking->current_latitude ? 15 : 5 }});

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap'
            }).addTo(map);

            var driverMarker = null;
            var trackingId = '{{ $booking->tracking_id }}';
            
            function updateLocation() {
                fetch('/api/track/ride/' + trackingId + '/location')
                    .then(response => response.json())
                    .then(data => {
                        document.getElementById('status-text').innerText = 'Status: ' + data.status;
                        
                        if (data.latitude && data.longitude) {
                            var latlng = [data.latitude, data.longitude];
                            
                            if (!driverMarker) {
                                driverMarker = L.marker(latlng).addTo(map);
                                driverMarker.bindPopup("Driver's Location").openPopup();
                                map.setView(latlng, 15);
                            } else {
                                driverMarker.setLatLng(latlng);
                            }
                        } else {
                            document.getElementById('status-text').innerText += ' (Waiting for driver GPS)';
                        }
                    })
                    .catch(error => console.error('Error fetching location:', error));
            }

            // Poll every 5 seconds
            setInterval(updateLocation, 5000);
            updateLocation(); // initial call
        });
    </script>
</body>
</html>
