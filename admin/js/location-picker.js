/**
 * Location Picker Component
 * Componente reutilizable para seleccionar ubicación en mapa con Leaflet
 * 
 * Dependencias:
 * - Leaflet CSS: <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
 * - Leaflet JS: <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
 * 
 * Uso:
 * const picker = new LocationPicker('map-container-id', {
 *     latInput: document.getElementById('lat'),
 *     lngInput: document.getElementById('lng'),
 *     initialLat: 51.8143,
 *     initialLng: 4.6650,
 *     initialZoom: 13
 * });
 */

class LocationPicker {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            console.error(`Container ${containerId} not found`);
            return;
        }
        
        // Opciones
        this.options = {
            latInput: options.latInput || null,
            lngInput: options.lngInput || null,
            initialLat: options.initialLat || 40.416775,
            initialLng: options.initialLng || -3.703790,
            initialZoom: options.initialZoom || 13,
            searchEnabled: options.searchEnabled !== false,
            onLocationSelected: options.onLocationSelected || null,
            markerIcon: options.markerIcon || null
        };
        
        this.map = null;
        this.marker = null;
        this.searchControl = null;
        
        this.init();
    }
    
    init() {
        // Crear el mapa
        this.map = L.map(this.container, {
            center: [this.options.initialLat, this.options.initialLng],
            zoom: this.options.initialZoom,
            zoomControl: true,
            scrollWheelZoom: true
        });
        
        // Añadir capa de tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19
        }).addTo(this.map);
        
        // Crear icono personalizado o usar el por defecto
        const icon = this.options.markerIcon || L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        
        // Si hay coordenadas iniciales en los inputs, usarlas
        if (this.options.latInput && this.options.lngInput) {
            const lat = parseFloat(this.options.latInput.value);
            const lng = parseFloat(this.options.lngInput.value);
            
            if (!isNaN(lat) && !isNaN(lng)) {
                this.setLocation(lat, lng);
            }
        } else {
            // Crear marcador inicial
            this.marker = L.marker([this.options.initialLat, this.options.initialLng], {
                icon: icon,
                draggable: true
            }).addTo(this.map);
            
            this.setupMarkerEvents();
        }
        
        // Click en el mapa para mover el marcador
        this.map.on('click', (e) => {
            this.setLocation(e.latlng.lat, e.latlng.lng);
        });
        
        // Sincronizar inputs con el mapa
        this.setupInputSync();
        
        // Ajustar tamaño del mapa
        setTimeout(() => {
            this.map.invalidateSize();
        }, 100);
    }
    
    setupMarkerEvents() {
        if (!this.marker) return;
        
        // Al arrastrar el marcador
        this.marker.on('dragend', (e) => {
            const position = e.target.getLatLng();
            this.updateInputs(position.lat, position.lng);
        });
        
        // Popup con coordenadas
        this.marker.on('click', () => {
            const pos = this.marker.getLatLng();
            this.marker.bindPopup(
                `<b>Ubicación seleccionada</b><br>` +
                `Lat: ${pos.lat.toFixed(6)}<br>` +
                `Lng: ${pos.lng.toFixed(6)}`
            ).openPopup();
        });
    }
    
    setupInputSync() {
        // Cuando se cambian manualmente los inputs, actualizar el mapa
        if (this.options.latInput) {
            this.options.latInput.addEventListener('change', () => {
                this.syncFromInputs();
            });
            this.options.latInput.addEventListener('blur', () => {
                this.syncFromInputs();
            });
        }
        
        if (this.options.lngInput) {
            this.options.lngInput.addEventListener('change', () => {
                this.syncFromInputs();
            });
            this.options.lngInput.addEventListener('blur', () => {
                this.syncFromInputs();
            });
        }
    }
    
    syncFromInputs() {
        if (!this.options.latInput || !this.options.lngInput) return;
        
        const lat = parseFloat(this.options.latInput.value);
        const lng = parseFloat(this.options.lngInput.value);
        
        if (!isNaN(lat) && !isNaN(lng)) {
            this.setLocation(lat, lng, false); // false = no actualizar inputs
        }
    }
    
    setLocation(lat, lng, updateInputs = true) {
        // Crear icono si no existe
        const icon = this.options.markerIcon || L.icon({
            iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
            shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
            iconSize: [25, 41],
            iconAnchor: [12, 41],
            popupAnchor: [1, -34],
            shadowSize: [41, 41]
        });
        
        // Si no hay marcador, crearlo
        if (!this.marker) {
            this.marker = L.marker([lat, lng], {
                icon: icon,
                draggable: true
            }).addTo(this.map);
            
            this.setupMarkerEvents();
        } else {
            // Mover marcador existente
            this.marker.setLatLng([lat, lng]);
        }
        
        // Centrar mapa
        this.map.flyTo([lat, lng], this.map.getZoom(), {
            duration: 0.5
        });
        
        // Actualizar inputs
        if (updateInputs) {
            this.updateInputs(lat, lng);
        }
        
        // Callback personalizado
        if (this.options.onLocationSelected) {
            this.options.onLocationSelected(lat, lng);
        }
    }
    
    updateInputs(lat, lng) {
        if (this.options.latInput) {
            this.options.latInput.value = lat.toFixed(8);
            // Disparar evento change
            this.options.latInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
        
        if (this.options.lngInput) {
            this.options.lngInput.value = lng.toFixed(8);
            // Disparar evento change
            this.options.lngInput.dispatchEvent(new Event('input', { bubbles: true }));
        }
    }
    
    getLocation() {
        if (this.marker) {
            const pos = this.marker.getLatLng();
            return { lat: pos.lat, lng: pos.lng };
        }
        return null;
    }
    
    destroy() {
        if (this.map) {
            this.map.remove();
        }
    }
}

// Función helper para crear rápidamente un location picker
function createLocationPicker(containerId, latInputId, lngInputId, options = {}) {
    const latInput = document.getElementById(latInputId);
    const lngInput = document.getElementById(lngInputId);
    
    return new LocationPicker(containerId, {
        ...options,
        latInput: latInput,
        lngInput: lngInput,
        initialLat: latInput.value ? parseFloat(latInput.value) : options.initialLat,
        initialLng: lngInput.value ? parseFloat(lngInput.value) : options.initialLng
    });
}
