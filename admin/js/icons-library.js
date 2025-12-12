/**
 * Librería de Iconos Material Symbols
 * Contiene categorías organizadas de iconos para usar en el gestor de viajes
 */

const MaterialIconsLibrary = {
    emergencia: [
        'emergency', 'e911_emergency', 'emergency_home', 'emergency_share', 
        'sos', 'medical_services', 'healing', 'vaccines', 'medication', 
        'health_and_safety'
    ],
    
    policia: [
        'local_police', 'security', 'shield', 'shield_person', 
        'verified_user', 'gavel', 'policy'
    ],
    
    hospital: [
        'local_hospital', 'emergency_home', 'medical_services', 
        'health_and_safety', 'favorite', 'monitor_heart', 'ecg_heart'
    ],
    
    bomberos: [
        'local_fire_department', 'fire_truck', 'fire_extinguisher', 'fireplace'
    ],
    
    telefono: [
        'phone', 'call', 'phone_in_talk', 'phone_enabled', 'contact_phone', 
        'phonelink_ring', 'ring_volume', 'call_end', 'phone_callback', 
        'phone_forwarded', 'phone_missed', 'phone_paused'
    ],
    
    soporte: [
        'support_agent', 'headset_mic', 'contact_support', 'help', 
        'help_center', 'live_help'
    ],
    
    lugares: [
        'home', 'hotel', 'apartment', 'cottage', 'villa', 'house', 'cabin', 
        'location_city', 'business', 'storefront', 'store', 'factory', 
        'warehouse', 'domain', 'place', 'map', 'fort'
    ],
    
    institucional: [
        'account_balance', 'museum', 'church', 'synagogue', 'mosque', 
        'temple_buddhist', 'temple_hindu'
    ],
    
    transporte: [
        'local_taxi', 'airport_shuttle', 'train', 'subway', 'directions_bus', 
        'directions_car', 'directions_boat', 'flight', 'flight_takeoff', 
        'flight_land', 'two_wheeler', 'electric_scooter', 'electric_bike', 
        'pedal_bike', 'directions_walk'
    ],
    
    restaurante: [
        'restaurant', 'local_cafe', 'local_bar', 'local_dining', 'lunch_dining', 
        'dinner_dining', 'breakfast_dining', 'ramen_dining', 'local_pizza', 
        'fastfood', 'coffee', 'liquor', 'wine_bar'
    ],
    
    compras: [
        'shopping_cart', 'shopping_bag', 'storefront', 'local_mall', 
        'local_grocery_store', 'local_convenience_store', 'sell', 
        'loyalty', 'redeem'
    ],
    
    entretenimiento: [
        'theater_comedy', 'sports_esports', 'sports_soccer', 'casino', 
        'sports_bar', 'celebration', 'attractions', 'festival', 'nightlife', 
        'pool', 'spa', 'tour'
    ],
    
    naturaleza: [
        'park', 'forest', 'landscape', 'terrain', 'water', 'beach_access', 
        'sailing', 'surfing', 'hiking', 'nature', 'nature_people', 'wind_power'
    ],
    
    ubicacion: [
        'location_on', 'place', 'map', 'my_location', 'near_me', 'explore', 
        'navigation', 'pin_drop', 'add_location', 'edit_location', 
        'gps_fixed', 'gps_not_fixed'
    ],
    
    informacion: [
        'info', 'info_outline', 'help', 'help_outline', 'announcement', 
        'campaign', 'notifications', 'notifications_active'
    ],
    
    tiempo: [
        'schedule', 'access_time', 'alarm', 'timer', 'hourglass_empty', 
        'update', 'history', 'watch_later', 'today', 'event', 'calendar_today'
    ],
    
    precio: [
        'confirmation_number', 'receipt', 'receipt_long', 'paid', 
        'attach_money', 'euro', 'currency_pound', 'currency_exchange', 
        'payments', 'credit_card', 'local_atm'
    ],
    
    varios: [
        'star', 'favorite', 'bookmark', 'label', 'flag', 'push_pin', 
        'priority_high', 'grade', 'verified', 'check_circle', 'cancel', 
        'error', 'warning', 'lightbulb', 'emoji_objects', 'key', 'lock', 
        'lock_open', 'visibility', 'visibility_off'
    ]
};

/**
 * Obtiene todos los iconos de todas las categorías sin duplicados
 * @returns {Array} Array con todos los iconos únicos
 */
function getAllIcons() {
    let allIcons = [];
    Object.values(MaterialIconsLibrary).forEach(category => {
        allIcons = [...allIcons, ...category];
    });
    return [...new Set(allIcons)];
}

/**
 * Busca iconos que coincidan con un término de búsqueda
 * @param {string} searchTerm - Término de búsqueda
 * @returns {Array} Array de iconos que coinciden
 */
function searchIcons(searchTerm) {
    const allIcons = getAllIcons();
    const term = searchTerm.toLowerCase().trim();
    
    if (term === '') {
        return allIcons;
    }
    
    return allIcons.filter(icon => icon.includes(term));
}

/**
 * Obtiene iconos de una categoría específica
 * @param {string} category - Nombre de la categoría
 * @returns {Array} Array de iconos de la categoría o array vacío si no existe
 */
function getIconsByCategory(category) {
    return MaterialIconsLibrary[category] || [];
}

/**
 * Obtiene todas las categorías disponibles
 * @returns {Array} Array con los nombres de las categorías
 */
function getCategories() {
    return Object.keys(MaterialIconsLibrary);
}

// Exportar para uso en módulos (si se necesita)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        MaterialIconsLibrary,
        getAllIcons,
        searchIcons,
        getIconsByCategory,
        getCategories
    };
}