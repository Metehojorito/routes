# 📤 Importación KMZ/KML - Guía de Usuario

## 🎯 Descripción

Esta funcionalidad permite importar archivos KMZ o KML exportados desde **Google My Maps** directamente a tu gestor de viajes. El sistema convierte automáticamente las capas del mapa en días del viaje y los puntos marcados en actividades.

## 🚀 Cómo Usar

### Paso 1: Exportar desde Google My Maps

1. Abre tu mapa en [Google My Maps](https://www.google.com/maps/d/)
2. Haz clic en el menú (⋮) junto al título del mapa
3. Selecciona **"Exportar a KML/KMZ"**
4. Marca la opción **"Exportar todo el mapa"**
5. Selecciona el formato **KMZ** (recomendado) o **KML**
6. Descarga el archivo

### Paso 2: Crear el Viaje

1. Accede al panel de administración
2. Haz clic en **"+ Nuevo Viaje"**
3. Completa los datos:
   - **Título del viaje**: Ej. "Róterdam GO"
   - **Fecha de inicio**: Ej. 04/12/2024
   - **Fecha de fin**: Ej. 09/12/2024
   - **Slug**: Se genera automáticamente
4. Guarda el viaje

### Paso 3: Importar el KMZ/KML

1. En el listado de viajes, localiza tu viaje
2. Haz clic en el icono **📤 (upload_file)** en la columna de acciones
3. Selecciona tu archivo KMZ o KML
4. Haz clic en **"Subir y Analizar"**

### Paso 4: Mapear Capas a Días

1. El sistema mostrará todas las capas encontradas en el archivo
2. Para cada capa, verás:
   - **Nombre de la capa**
   - **Número de puntos**
   - **Vista previa de los primeros 3 puntos**
3. Asigna cada capa a un día del viaje usando el selector desplegable
4. Haz clic en **"Crear Días y Actividades"**

### Paso 5: Verificar y Ajustar

1. El sistema te redirigirá al listado de días
2. Verás un mensaje de éxito indicando cuántos días y actividades se crearon
3. Puedes editar manualmente:
   - Títulos de días
   - Descripciones de actividades
   - Iconos y colores
   - Orden de actividades
   - Agregar secciones (Mañana, Tarde, etc.)

## 📋 Estructura de Google My Maps

### Organización Recomendada

Para mejores resultados, organiza tu mapa de Google My Maps de la siguiente manera:

```
Mi Viaje a España
├── 📁 Capa 1: Día en Madrid
│   ├── 📍 Puerta del Sol
│   ├── 📍 Plaza Mayor
│   ├── 📍 Palacio Real
│   └── 📍 Museo del Prado
│
├── 📁 Capa 2: Excursión a Toledo
│   ├── 📍 Catedral de Toledo
│   ├── 📍 Alcázar de Toledo
│   └── 📍 Sinagoga del Tránsito
│
└── 📁 Capa 3: Visita a Segovia
    ├── 📍 Acueducto de Segovia
    ├── 📍 Alcázar de Segovia
    └── 📍 Catedral de Segovia
```

### Datos que se Importan

Por cada **punto (Placemark)** se importa:
- ✅ **Nombre del punto** → Título de la actividad
- ✅ **Descripción** → Descripción de la actividad
- ✅ **Coordenadas (lat/lng)** → Ubicación en el mapa

### Datos que NO se Importan

- ❌ **Iconos personalizados** (se usa 'place' por defecto)
- ❌ **Colores de puntos** (se usa 'primary' por defecto)
- ❌ **Imágenes adjuntas**
- ❌ **Rutas o líneas** (solo puntos)
- ❌ **Polígonos o áreas**

## 🎨 Personalización Post-Importación

Después de importar, puedes personalizar:

### En los Días
- **Título**: Editar el nombre del día
- **Descripción**: Agregar contexto adicional
- **Centro del mapa**: Ajustar la vista del mapa
- **Zoom**: Cambiar el nivel de zoom
- **Secciones**: Crear secciones (Mañana, Tarde, Noche)

### En las Actividades
- **Título**: Renombrar la actividad
- **Descripción**: Ampliar información
- **Icono**: Cambiar a iconos de Material Symbols (flight, restaurant, museum, etc.)
- **Color**: Asignar categoría (primary, secondary)
- **Orden**: Reordenar con drag & drop
- **Sección**: Asignar a una sección del día
- **Detalles**: Agregar horarios, precios, notas

## 🔧 Solución de Problemas

### Error: "Formato de archivo no válido"
- **Causa**: El archivo no es KMZ o KML
- **Solución**: Verifica que exportaste correctamente desde Google My Maps

### Error: "No se encontraron capas"
- **Causa**: El archivo KML no contiene carpetas (Folders)
- **Solución**: En Google My Maps, organiza tus puntos en capas antes de exportar

### Error: "No se encontraron puntos válidos"
- **Causa**: Las capas no contienen puntos con coordenadas válidas
- **Solución**: Asegúrate de que cada capa tiene al menos un punto marcado

### Error: "El archivo es demasiado grande"
- **Causa**: El archivo supera los 10MB
- **Solución**: Divide tu viaje en múltiples mapas o elimina datos innecesarios

### Días no se crean en el orden esperado
- **Causa**: Asignaste las capas a días incorrectos
- **Solución**: Puedes reordenar los días manualmente con drag & drop después de importar

## 📊 Ejemplo Completo

### Escenario
Quieres crear un viaje de 3 días a Castilla y León.

### 1. En Google My Maps
```
Viaje a Castilla y León
├── Capa 1: Madrid (4 puntos)
├── Capa 2: Toledo (3 puntos)
└── Capa 3: Segovia (4 puntos)
```

### 2. En el Gestor de Viajes
1. Crear viaje:
   - Título: "Castilla y León"
   - Fecha inicio: 01/01/2026
   - Fecha fin: 03/01/2026
   
2. Importar KMZ

3. Mapear capas:
   - "Madrid" → Día 1 (01/01/2026)
   - "Toledo" → Día 2 (02/01/2026)
   - "Segovia" → Día 3 (03/01/2026)

### 3. Resultado
```
✅ Se crearon 3 días y 11 actividades exitosamente

Día 1: Madrid (01/01/2026)
├── Puerta del Sol
├── Plaza Mayor
├── Palacio Real
└── Museo del Prado

Día 2: Toledo (02/01/2026)
├── Catedral de Toledo
├── Alcázar de Toledo
└── Sinagoga del Tránsito

Día 3: Segovia (03/01/2026)
├── Acueducto de Segovia
├── Alcázar de Segovia
├── Catedral de Segovia
└── Casa de los Picos
```

## 🎯 Mejores Prácticas

### ✅ Recomendaciones

1. **Organiza antes de exportar**: Crea las capas en Google My Maps con nombres descriptivos
2. **Una capa = Un día**: Facilita el mapeo posterior
3. **Nombres claros**: Usa nombres descriptivos para puntos y capas
4. **Agregar descripciones**: Incluye información útil en cada punto
5. **Verificar coordenadas**: Asegúrate de que los puntos están bien ubicados

### ❌ Evitar

1. **No mezclar días en una capa**: Cada capa debe representar un día o actividad específica
2. **No usar capas vacías**: Elimina capas sin puntos antes de exportar
3. **No incluir puntos duplicados**: Revisa que no haya puntos repetidos
4. **No usar nombres genéricos**: "Punto 1", "Lugar 2" no son descriptivos

## 🔄 Importación Múltiple

### ¿Puedo importar varios KMZ para el mismo viaje?
Sí, puedes importar múltiples archivos KMZ para el mismo viaje. El sistema:
- ✅ Detecta si ya existe un día con ese número
- ✅ Agrega actividades al día existente
- ✅ No duplica días

### ¿Puedo sobrescribir días existentes?
No, el sistema no sobrescribe días. Si quieres reemplazar un día:
1. Elimina el día manualmente
2. Importa el KMZ de nuevo

## 📞 Soporte

### Archivos de Prueba
En el repositorio encontrarás:
- `test_viaje.kml` - Archivo KML de ejemplo
- `test_viaje.kmz` - Archivo KMZ de ejemplo

### Reportar Problemas
Si encuentras algún error:
1. Verifica que seguiste todos los pasos correctamente
2. Revisa la sección de solución de problemas
3. Contacta al administrador con:
   - Mensaje de error exacto
   - Archivo KMZ/KML que intentaste importar
   - Navegador y versión

## 🚀 Próximas Mejoras

Funcionalidades planeadas para futuras versiones:
- [ ] Importación por día individual
- [ ] Previsualización de puntos en mapa antes de importar
- [ ] Edición de iconos durante la importación
- [ ] Importación de imágenes adjuntas en KML
- [ ] Soporte para rutas (LineString)
- [ ] Detección automática de tipo de actividad por nombre
- [ ] Importación de colores personalizados
- [ ] Exportación de viajes a KMZ

## 📚 Recursos Adicionales

- [Google My Maps - Ayuda oficial](https://support.google.com/mymaps/)
- [Especificación KML](https://developers.google.com/kml/documentation/)
- [Material Symbols - Iconos disponibles](https://fonts.google.com/icons)

---

**Versión**: 1.0.0  
**Fecha**: Diciembre 2024  
**Autor**: Gestor de Viajes
