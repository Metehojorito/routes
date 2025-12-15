# 🌍 Gestor de Viajes

Sistema web completo para planificar, gestionar y visualizar itinerarios de viaje con mapas interactivos y diseño responsive.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-blue.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)
![Leaflet](https://img.shields.io/badge/Leaflet-1.9.4-green.svg)

## ✨ Características

### 🎯 Funcionalidades Principales

- **Gestión Completa de Viajes**: Crea y administra múltiples viajes con información detallada
- **Itinerarios por Días**: Organiza actividades día a día con secciones personalizables
- **🆕 Mapas con Leaflet + OpenStreetMap**: Sin API key, 100% gratuito, más rápido
- **📍 Location Picker Interactivo**: Selecciona ubicaciones con un click en el mapa
- **🎨 Pines Personalizados**: Sube tu propio pin (GIF/PNG/WebP) para cada viaje
- **Diseño Responsive**: Interfaz optimizada para móviles y tablets
- **Modo Oscuro/Claro**: Tema adaptable según preferencias del usuario
- **Personalización Visual**: Paleta de colores configurable por viaje con 8 plantillas predefinidas
- **Drag & Drop**: Reordena elementos fácilmente arrastrando y soltando
- **Selector de Iconos**: Más de 150 iconos Material Symbols organizados por categorías

### 📱 Área Pública

- Vista de portada atractiva con imagen de fondo
- Menú lateral con información de alojamiento y contactos de emergencia
- Listado de días del viaje con información detallada
- Vista de día individual con:
  - **🗺️ Mapa interactivo Leaflet** con marcadores de ubicaciones
  - Pines personalizados por viaje
  - Actividades organizadas por secciones (Mañana, Tarde, Noche, etc.)
  - Detalles adicionales (horarios, precios, confirmaciones)
  - Enlaces directos a Google Maps para navegación

### 🔐 Panel de Administración

#### Gestión de Viajes
- Crear/editar/eliminar viajes
- Subida de imagen de portada
- **🆕 Subida de pin personalizado** para mapas (GIF/PNG/WebP)
- Configuración de fechas y slug único
- Importación de archivo KMZ/KML donde se asignan las capas a días del viaje
- Personalización de colores con 8 plantillas predefinidas:
  - 🏖️ Tropical (Cian y Dorado)
  - 🏔️ Montaña (Verde bosque y Marrón)
  - 💕 Romántico (Rosa y Púrpura)
  - 🏯 Cultural (Carmesí y Dorado)
  - 🏙️ Urbano (Índigo y Rosa)
  - 🌿 Naturaleza (Esmeralda y Ámbar)
  - 🌊 Océano (Azul cielo y Cian)
  - ⚪ Minimalista (Gris slate)

#### Gestión de Días
- Crear días del viaje con título y descripción
- **🆕 Location Picker**: Selecciona el centro del mapa con un click
- Configurar zoom del mapa
- Ordenar días mediante drag & drop
- Vista previa de cada día

#### Gestión de Secciones
- Organizar días en secciones (Mañana, Tarde, Noche, Opcional)
- Reordenar secciones con drag & drop

#### Gestión de Actividades
- Crear actividades con icono y descripción
- **🆕 Location Picker**: Selecciona ubicación en mapa interactivo
- Agregar detalles adicionales (horarios, precios, etc.)
- Selector visual de iconos Material Symbols
- Mover actividades entre secciones con drag & drop
- Reordenar dentro de cada sección

#### Información Adicional
- **Alojamientos**: Guarda información de hoteles/apartamentos con **Location Picker**
- **Contactos de Emergencia**: Números importantes (112, policía, embajada, hotel)

## 🛠️ Tecnologías Utilizadas

### Backend
- **PHP 7.4+**: Lenguaje principal del servidor
- **MySQL 5.7+**: Base de datos relacional
- **PDO**: Capa de abstracción de base de datos

### Frontend
- **HTML5/CSS3**: Estructura y estilos
- **TailwindCSS**: Framework CSS utility-first
- **JavaScript Vanilla**: Interactividad sin dependencias
- **Material Symbols**: Librería de iconos de Google
- **🆕 Leaflet 1.9.4**: Mapas interactivos sin API key
- **🆕 OpenStreetMap**: Tiles de mapa gratuitos

### Características Técnicas
- **Drag & Drop nativo HTML5**: Sin librerías externas
- **Responsive Design**: Mobile-first approach
- **PWA-ready**: Optimizado para funcionar como app
- **AJAX**: Actualizaciones sin recargar página
- **Session Management**: Sistema de sesiones PHP
- **🆕 Location Picker**: Componente reutilizable para selección de ubicaciones

## 📋 Requisitos del Sistema

- PHP >= 7.4
- MySQL >= 5.7 o MariaDB >= 10.2
- Servidor web (Apache/Nginx)
- Extensiones PHP:
  - PDO
  - pdo_mysql
  - mbstring
  - fileinfo
  - gd (opcional, para manipulación de imágenes)

## 🚀 Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/Metehojorito/routes.git
cd gestor-viajes
```

### 2. Configurar la base de datos

```bash
# Crear la base de datos
mysql -u root -p
CREATE DATABASE gestor_viajes CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;

# Importar el schema
mysql -u root -p gestor_viajes < gestor_viajes.sql
```

### 3. Configurar conexión a BD

Edita `config/database.php`:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'gestor_viajes');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
```

### 4. 🆕 Migración: Pin Personalizado (Nuevo)

Si actualizas desde una versión anterior, ejecuta:

```bash
mysql -u root -p gestor_viajes < add_pin_mapa_field.sql
```

### 5. Configurar permisos

```bash
# Crear carpeta de imágenes y dar permisos de escritura
mkdir -p images
chmod 755 images
chown www-data:www-data images  # En producción

# 🆕 Las carpetas de pines se crean automáticamente
# images/[viaje_id]/maps/ se genera al subir el primer pin
```

### 6. Acceder al sistema

- **Área pública**: `http://localhost/gestor-viajes/`
- **Panel admin**: `http://localhost/gestor-viajes/admin/`
  - Usuario: `admin`
  - Contraseña: `admin123`

⚠️ **Importante**: Cambia la contraseña por defecto inmediatamente después del primer login.

## 📁 Estructura del Proyecto

```
gestor-viajes/
├── admin/                    # Panel de administración
│   ├── js/                   # JavaScript del admin
│   │   ├── icons-library.js  # Librería de iconos
│   │   ├── icon-picker.js    # Selector de iconos
│   │   └── location-picker.js # 🆕 Selector de ubicación con mapa
│   ├── partials/             # Componentes reutilizables
│   │   └── actividad_item.php
│   ├── index.php             # Dashboard principal
│   ├── login.php             # Login del admin
│   ├── viaje_form.php        # Formulario de viajes
│   ├── dias_list.php         # Listado de días
│   ├── actividades_list.php  # Listado de actividades
│   └── ...                   # Otros archivos del admin
├── config/
│   └── database.php          # Configuración de BD
├── images/                   # Imágenes subidas (auto-creada)
│   └── [viaje_id]/
│       ├── portada/          # Imágenes de portada
│       └── maps/             # 🆕 Pines personalizados
├── index.php                 # Portada pública
├── menu.php                  # Menú de días del viaje
├── dia.php                   # Vista de día individual (Leaflet)
├── gestor_viajes.sql         # Schema de la base de datos
├── add_pin_mapa_field.sql    # 🆕 Migración para pines
└── README.md                 # Este archivo
```

## 🎨 Personalización

### Colores del Viaje

Cada viaje puede tener su propia paleta de colores. Se configuran 6 colores:
- **Primary**: Color principal (iconos, enlaces)
- **Secondary**: Color secundario (actividades alternativas)
- **Background Light**: Fondo en modo claro
- **Background Dark**: Fondo en modo oscuro
- **Card Light**: Tarjetas en modo claro
- **Card Dark**: Tarjetas en modo oscuro

### 🆕 Pines Personalizados

Cada viaje puede tener su propio pin para los mapas:

1. **Formatos soportados**: GIF (animado), PNG, WebP
2. **Tamaño recomendado**: 40x40px
3. **Ubicación**: `images/[viaje_id]/maps/pin.[gif|png|webp]`
4. **Fallback**: Si no hay pin personalizado, usa `images/pin.gif`

### Iconos Material Symbols

El sistema incluye más de 150 iconos organizados en categorías:
- Emergencia, Policía, Hospital, Bomberos
- Teléfono, Soporte
- Lugares, Institucional
- Transporte (incluye `directions_walk`)
- Restaurante, Compras
- Entretenimiento, Naturaleza
- Ubicación, Información
- Tiempo, Precio
- Varios

Para agregar nuevos iconos, edita `admin/js/icons-library.js`.

## 🗺️ Mapas: Leaflet vs Google Maps

El proyecto incluye **dos versiones** de mapas:

### ✅ Leaflet + OpenStreetMap (Recomendado - Por Defecto)

**Archivo**: `dia.php`

**Ventajas**:
- ✅ **100% GRATIS** - Sin API keys ni límites
- ✅ **Sin configuración** - Funciona inmediatamente
- ✅ **Más rápido** - Librería 62% más ligera
- ✅ **Soporta WebP** - Para pines personalizados
- ✅ **Múltiples estilos** - 5+ estilos de mapa incluidos
- ✅ **Privacidad** - Sin tracking de Google

**Estilos disponibles** (cambiar en línea ~226 de dia.php):
```javascript
// OpenStreetMap (por defecto - claro)
L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png')

// CartoDB Dark Matter (modo oscuro elegante)
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png')

// CartoDB Positron (minimalista claro)
L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png')
```

### 📍 Location Picker (Admin)

**Archivo**: `admin/js/location-picker.js`

Selector de ubicación interactivo para formularios del admin:

**Características**:
- 🗺️ Mapa interactivo en formularios
- 🎯 Click para seleccionar ubicación
- 🔄 Sincronización bidireccional (mapa ↔ inputs)
- 🎯 Arrastrar marcador para ajustar
- ✅ Sin API key necesaria

**Se usa en**:
- `actividad_form.php` - Ubicar actividades
- `dia_form.php` - Definir centro del mapa
- `alojamiento_form.php` - Ubicar hoteles

Ver `GUIA_LOCATION_PICKER.md` para implementación completa.

### ⚠️ Google Maps (Alternativo)

Si prefieres Google Maps, sigue estos pasos:

1. **Obtén API Key**: [Google Cloud Console](https://console.cloud.google.com/)
2. **Habilita**: "Maps JavaScript API"
3. **Configura** en la base de datos:

```sql
INSERT INTO configuracion (clave, valor) 
VALUES ('google_maps_api_key', 'TU_API_KEY_AQUI')
ON DUPLICATE KEY UPDATE valor = 'TU_API_KEY_AQUI';
```

4. **Reemplaza** `dia.php` por la versión con Google Maps (no incluida por defecto)

**Nota**: Google Maps requiere facturación después de cierto uso. Leaflet es completamente gratis.

## 🔒 Seguridad

### Consideraciones Importantes

1. **Cambiar contraseña por defecto** inmediatamente
2. **Usar HTTPS en producción**
3. **Configurar permisos restrictivos** en carpetas
4. **Validar y sanitizar** todas las entradas de usuario
5. **Mantener PHP y MySQL actualizados**
6. **Restringir acceso** a `/admin/` mediante .htaccess si es necesario

### Crear nuevo usuario admin

```php
// Ejecutar en tu servidor
$password = password_hash('nueva_contraseña', PASSWORD_DEFAULT);
// Insertar en la tabla admin_users
```

## 🐛 Solución de Problemas

### Error de conexión a BD
```
Error de conexión: SQLSTATE[HY000] [1045] Access denied
```
**Solución**: Verifica usuario/contraseña en `config/database.php`

### Imágenes no se suben
**Solución**: Verifica permisos de carpeta `images/`:
```bash
chmod 755 images -R
chown www-data:www-data images -R
```

### Mapas no se muestran

**Con Leaflet (por defecto)**:
- ✅ No necesita configuración, debería funcionar inmediatamente
- Verifica la consola del navegador para errores de JavaScript

**Con Google Maps (si lo usas)**:
1. Verifica que la API Key esté configurada en la BD
2. Comprueba que la API esté habilitada en Google Cloud Console
3. Revisa la consola del navegador para errores

### 🆕 Location Picker no funciona

1. Verifica que `location-picker.js` esté en `/admin/js/`
2. Comprueba que Leaflet CSS y JS estén cargados
3. Revisa la consola del navegador para errores
4. Asegúrate de que los inputs tengan `id="lat"` e `id="lng"`

### 🆕 Pin personalizado no aparece

```bash
# Verificar permisos
ls -la images/[viaje_id]/maps/

# Corregir permisos
chmod 755 images/[viaje_id]/maps/
chmod 644 images/[viaje_id]/maps/pin.*
```

### Drag & Drop no funciona en móvil
**Nota**: El drag & drop actual solo funciona con mouse. Para móviles se necesitaría implementar eventos touch adicionales.

## 📝 Uso

### Crear tu primer viaje

1. Accede al panel admin y haz login
2. Clic en "+ Nuevo Viaje"
3. Rellena información básica y sube una imagen de portada
4. **🆕 (Opcional)** Sube un pin personalizado para los mapas
5. Elige una plantilla de colores o personaliza manualmente
6. Guarda el viaje

### Agregar días al viaje

1. Desde el dashboard, clic en "Días" del viaje
2. "+ Nuevo Día" y configura fecha, título
3. **🆕 Usa el Location Picker**: Haz click en el mapa para definir el centro
4. Define el zoom del mapa
5. Opcionalmente crea secciones para organizar el día

### Agregar actividades

1. Entra en "Actividades" del día
2. "+ Nueva Actividad"
3. Selecciona icono, título, descripción
4. **🆕 Usa el Location Picker**: Haz click en el mapa o arrastra el marcador
5. Agrega detalles adicionales (horarios, precios)

### Publicar el viaje

El viaje es accesible públicamente en:
```
http://tu-dominio.com/?viaje=slug-del-viaje
```

## 🆕 Novedades en v2.0

### ✨ Características Nuevas

#### 🗺️ Migración a Leaflet + OpenStreetMap
- **Sin API key**: Elimina dependencia de Google Maps
- **100% gratuito**: Sin límites de uso
- **Más rápido**: Librería 62% más ligera
- **Múltiples estilos**: 5+ estilos de mapa disponibles
- **Mejor privacidad**: Sin tracking de terceros

#### 📍 Location Picker Interactivo
- **Selector visual**: Click en mapa para seleccionar ubicación
- **Drag & drop**: Arrastra marcador para ajustar
- **Sincronización**: Mapa ↔ Inputs bidireccional
- **3 formularios**: Actividades, días y alojamientos

#### 🎨 Pines Personalizados
- **Por viaje**: Cada viaje puede tener su pin único
- **Formatos**: GIF (animado), PNG, WebP
- **Fácil**: Sube desde el formulario de viaje
- **Automático**: Se usa en todos los mapas del viaje

### 📚 Documentación Adicional

- **IMPLEMENTACION_PIN_MAPA.md** - Guía de pines personalizados
- **COMPARACION_MAPAS.md** - Comparativa Leaflet vs Google Maps
- **GUIA_LOCATION_PICKER.md** - Implementación del selector de ubicación

## 🤝 Contribuciones

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/AmazingFeature`)
3. Commit tus cambios (`git commit -m 'Add some AmazingFeature'`)
4. Push a la rama (`git push origin feature/AmazingFeature`)
5. Abre un Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver archivo `LICENSE` para más detalles.

## 👥 Autores

- **Metehojorito** - *Desarrollo inicial* - [Metehojorito](https://github.com/Metehojorito)

## 🙏 Agradecimientos

- [Leaflet](https://leafletjs.com/) - Mapas interactivos sin API key
- [OpenStreetMap](https://www.openstreetmap.org/) - Datos cartográficos libres
- [Material Symbols](https://fonts.google.com/icons) - Iconos
- [TailwindCSS](https://tailwindcss.com/) - Framework CSS
- [PHP](https://www.php.net/) - Lenguaje backend

## 📧 Contacto

Para preguntas o sugerencias:
- Issues: [GitHub Issues](https://github.com/Metehojorito/routes/issues)

## 🎯 Roadmap

### Próximas características:
- [ ] Exportar itinerario a PDF
- [ ] Compartir viaje mediante enlace
- [ ] Modo offline para la app
- [ ] Multi-idioma
- [ ] Importar desde Google Maps
- [ ] API REST para integración con apps móviles
- [ ] Calculadora de presupuesto
- [ ] Galería de fotos por día

---

⭐ Si te gusta este proyecto, dale una estrella en GitHub!

**Versión actual**: 2.0.0  
**Última actualización**: Diciembre 2025
