# Arquitectura de Importación KMZ/KML

## 📋 Resumen

Sistema de importación de archivos KMZ/KML de Google My Maps para crear automáticamente viajes, días y actividades en el gestor de viajes.

## 🎯 Flujo de Usuario

1. **Crear viaje** → El usuario crea un viaje con título, fechas inicio/fin
2. **Importar KMZ** → Desde el listado de viajes, click en botón "Importar KMZ"
3. **Subir archivo** → Seleccionar archivo KMZ o KML
4. **Mapear capas** → Asignar cada capa del KMZ a un día del viaje
5. **Procesar** → El sistema crea días y actividades automáticamente

## 🏗️ Componentes

### 1. Botón de Importación (admin/index.php)
- Nuevo icono en la columna de acciones: `upload_file`
- Link a: `kmz_import.php?viaje_id={id}`

### 2. Página de Importación (admin/kmz_import.php)
**Funcionalidades:**
- Formulario de subida de archivo (KMZ o KML)
- Validación de formato
- Extracción de capas del archivo
- Interfaz de mapeo capa → día

**Interfaz de Mapeo:**
```
Capa 1: "Día en Róterdam"     → Día 1 (04/12/2024) [Dropdown]
Capa 2: "Visita a La Haya"    → Día 2 (05/12/2024) [Dropdown]
Capa 3: "Excursión a Delft"   → Día 3 (06/12/2024) [Dropdown]
```

### 3. Procesador KMZ (admin/kmz_process.php)
**Funcionalidades:**
- Parsear archivo KMZ/KML
- Extraer capas (Folders en KML)
- Extraer placemarks (puntos) de cada capa
- Crear días en la base de datos
- Crear actividades con lat/lng

## 📦 Estructura de Archivos KMZ/KML

### KMZ (Comprimido)
```
archivo.kmz
└── doc.kml (archivo XML dentro del ZIP)
```

### KML (XML)
```xml
<kml>
  <Document>
    <Folder>
      <name>Capa 1</name>
      <Placemark>
        <name>Punto 1</name>
        <description>Descripción del punto</description>
        <Point>
          <coordinates>4.4768,51.9244,0</coordinates>
        </Point>
      </Placemark>
    </Folder>
  </Document>
</kml>
```

## 🔄 Flujo de Procesamiento

### Paso 1: Extracción
```php
1. Verificar extensión (.kmz o .kml)
2. Si es KMZ → Extraer ZIP → Obtener doc.kml
3. Si es KML → Leer directamente
4. Parsear XML con SimpleXML
```

### Paso 2: Análisis
```php
1. Obtener todos los <Folder> (capas)
2. Para cada Folder:
   - Extraer nombre de la capa
   - Extraer todos los <Placemark> (puntos)
   - Para cada Placemark:
     * Nombre
     * Descripción
     * Coordenadas (lng, lat, alt)
```

### Paso 3: Mapeo
```php
1. Calcular días disponibles del viaje (fecha_inicio → fecha_fin)
2. Mostrar formulario:
   - Lista de capas encontradas
   - Dropdown para seleccionar día (1, 2, 3...)
   - Botón "Procesar"
```

### Paso 4: Creación
```php
1. Para cada capa mapeada:
   - Crear día en tabla `dias_viaje`:
     * viaje_id
     * numero_dia
     * fecha (calculada desde fecha_inicio)
     * titulo (nombre de la capa)
     * centro_mapa_lat/lng (promedio de puntos)
     * zoom_mapa (14 por defecto)
   
   - Crear actividades en tabla `actividades`:
     * dia_id
     * titulo (nombre del placemark)
     * descripcion (descripción del placemark)
     * lat/lng (coordenadas)
     * icono ('place' por defecto)
     * color_categoria ('primary' por defecto)
     * orden (secuencial)
```

## 🗄️ Tablas Afectadas

### `dias_viaje`
```sql
INSERT INTO dias_viaje (
  viaje_id,
  numero_dia,
  fecha,
  titulo,
  descripcion,
  centro_mapa_lat,
  centro_mapa_lng,
  zoom_mapa,
  orden
) VALUES (?, ?, ?, ?, '', ?, ?, 14, ?)
```

### `actividades`
```sql
INSERT INTO actividades (
  dia_id,
  seccion_id,
  titulo,
  descripcion,
  icono,
  color_categoria,
  lat,
  lng,
  orden
) VALUES (?, NULL, ?, ?, 'place', 'primary', ?, ?, ?)
```

## 🛠️ Tecnologías

- **PHP 7.4+** con SimpleXML
- **ZipArchive** para extraer KMZ
- **PDO** para base de datos
- **Tailwind CSS** para interfaz
- **Material Symbols** para iconos

## 📝 Validaciones

### Archivo
- ✅ Extensión: .kmz o .kml
- ✅ Tamaño máximo: 10MB
- ✅ Formato XML válido
- ✅ Contiene al menos 1 capa

### Datos
- ✅ Cada capa debe tener al menos 1 punto
- ✅ Coordenadas válidas (lat: -90 a 90, lng: -180 a 180)
- ✅ Número de día dentro del rango del viaje

## 🎨 Interfaz de Usuario

### Página de Importación
```
┌─────────────────────────────────────────┐
│ Importar KMZ/KML - Viaje: Róterdam GO   │
├─────────────────────────────────────────┤
│                                         │
│ 📤 Seleccionar archivo KMZ/KML          │
│ [Elegir archivo] archivo.kmz            │
│                                         │
│ [Subir y Analizar]                      │
└─────────────────────────────────────────┘
```

### Página de Mapeo
```
┌─────────────────────────────────────────┐
│ Mapear Capas a Días                     │
├─────────────────────────────────────────┤
│                                         │
│ Capa: "Día en Róterdam" (5 puntos)     │
│ Asignar a: [Día 1 - 04/12/2024 ▼]      │
│                                         │
│ Capa: "Visita a La Haya" (8 puntos)    │
│ Asignar a: [Día 2 - 05/12/2024 ▼]      │
│                                         │
│ [Crear Días y Actividades]              │
└─────────────────────────────────────────┘
```

## 🔐 Seguridad

- ✅ Validar sesión de administrador
- ✅ Verificar permisos de escritura en carpeta temporal
- ✅ Limpiar archivos temporales después de procesar
- ✅ Escapar datos antes de insertar en BD
- ✅ Validar que el viaje_id pertenece al usuario

## 📊 Casos de Uso

### Caso 1: Importación Exitosa
1. Usuario sube KMZ con 3 capas
2. Sistema extrae 3 capas con 15 puntos totales
3. Usuario mapea cada capa a un día
4. Sistema crea 3 días y 15 actividades
5. Redirección a listado de días

### Caso 2: Archivo Inválido
1. Usuario sube archivo .txt
2. Sistema detecta formato inválido
3. Muestra error: "Formato no válido"
4. Usuario puede intentar de nuevo

### Caso 3: Sin Capas
1. Usuario sube KML sin capas
2. Sistema detecta 0 capas
3. Muestra error: "No se encontraron capas"
4. Usuario puede intentar de nuevo

## 🚀 Próximas Mejoras

- [ ] Importación por día individual
- [ ] Previsualización de puntos en mapa
- [ ] Edición de iconos durante importación
- [ ] Importación de imágenes adjuntas en KML
- [ ] Soporte para rutas (LineString)
- [ ] Detección automática de tipo de actividad
