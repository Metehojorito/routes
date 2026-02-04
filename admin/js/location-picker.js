/**
 * Location Picker Component
 * Componente reutilizable para seleccionar ubicaciones en un mapa con Leaflet
 * Ahora con soporte para tiles de CartoDB (diseño más limpio)
 * 
 * @param {string} mapContainerId - ID del div contenedor del mapa
 * @param {string} latInputId - ID del input de latitud
 * @param {string} lngInputId - ID del input de longitud
 * @param {Object} options - Opciones de configuración
 * @returns {Object} - Objeto con métodos públicos del picker
 */
function createLocationPicker(mapContainerId, latInputId, lngInputId, options = {}) {
    const defaults = {
        initialLat: 51.8143,
        initialLng: 4.6650,
        initialZoom: 13,
        useCartoDB: false  // Nueva opción para usar tiles de CartoDB
    };
    
    const config = { ...defaults, ...options };
    
    // Elementos del DOM
    const mapContainer = document.getElementById(mapContainerId);
    const latInput = document.getElementById(latInputId);
    const lngInput = document.getElementById(lngInputId);
    
    if (!mapContainer || !latInput || !lngInput) {
        console.error('LocationPicker: No se encontraron todos los elementos necesarios');
        return null;
    }
    
    // Determinar coordenadas iniciales
    let currentLat = parseFloat(latInput.value) || config.initialLat;
    let currentLng = parseFloat(lngInput.value) || config.initialLng;
    
    // Inicializar mapa
    const map = L.map(mapContainerId, {
        center: [currentLat, currentLng],
        zoom: config.initialZoom,
        zoomControl: true,
        scrollWheelZoom: true,
        dragging: true,
        touchZoom: true,
        doubleClickZoom: true
    });
    
    // Seleccionar tiles según configuración
    if (config.useCartoDB) {
        // Tiles de CartoDB - diseño más limpio y simple
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
            subdomains: 'abcd',
            maxZoom: 20
        }).addTo(map);
    } else {
        // Tiles por defecto de OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19
        }).addTo(map);
    }
    
    // Icono personalizado rojo
    const redIcon = L.icon({
        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/images/marker-shadow.png',
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41]
    });
    
    // Crear marcador inicial si hay coordenadas
    let marker = null;
    if (latInput.value && lngInput.value) {
        marker = L.marker([currentLat, currentLng], { 
            icon: redIcon,
            draggable: true 
        }).addTo(map);
        
        // Actualizar coordenadas al arrastrar
        marker.on('dragend', function(e) {
            const position = e.target.getLatLng();
            updateCoordinates(position.lat, position.lng);
        });
    }
    
    // Función para actualizar coordenadas
    function updateCoordinates(lat, lng) {
        latInput.value = lat.toFixed(8);
        lngInput.value = lng.toFixed(8);
        currentLat = lat;
        currentLng = lng;
    }
    
    // Click en el mapa para colocar/mover marcador
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        
        if (marker) {
            marker.setLatLng([lat, lng]);
        } else {
            marker = L.marker([lat, lng], { 
                icon: redIcon,
                draggable: true 
            }).addTo(map);
            
            marker.on('dragend', function(e) {
                const position = e.target.getLatLng();
                updateCoordinates(position.lat, position.lng);
            });
        }
        
        updateCoordinates(lat, lng);
    });
    
    // Actualizar mapa cuando se escriban coordenadas manualmente
    function handleManualCoordinates() {
        const lat = parseFloat(latInput.value);
        const lng = parseFloat(lngInput.value);
        
        if (!isNaN(lat) && !isNaN(lng)) {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], { 
                    icon: redIcon,
                    draggable: true 
                }).addTo(map);
                
                marker.on('dragend', function(e) {
                    const position = e.target.getLatLng();
                    updateCoordinates(position.lat, position.lng);
                });
            }
            
            map.setView([lat, lng], config.initialZoom);
            currentLat = lat;
            currentLng = lng;
        }
    }
    
    latInput.addEventListener('change', handleManualCoordinates);
    lngInput.addEventListener('change', handleManualCoordinates);
    
    // Invalidar tamaño del mapa después de inicialización
    setTimeout(() => {
        map.invalidateSize();
    }, 100);
    
    // API pública
    return {
        map: map,
        marker: marker,
        setPosition: function(lat, lng) {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng], { 
                    icon: redIcon,
                    draggable: true 
                }).addTo(map);
                
                marker.on('dragend', function(e) {
                    const position = e.target.getLatLng();
                    updateCoordinates(position.lat, position.lng);
                });
            }
            map.setView([lat, lng], config.initialZoom);
            updateCoordinates(lat, lng);
        },
        getPosition: function() {
            return { lat: currentLat, lng: currentLng };
        },
        clearMarker: function() {
            if (marker) {
                map.removeLayer(marker);
                marker = null;
            }
            latInput.value = '';
            lngInput.value = '';
        }
    };
}
