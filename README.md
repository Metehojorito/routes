# 🌍 Gestor de Viajes

Sistema web completo para planificar, gestionar y visualizar itinerarios de viaje con mapas interactivos y diseño responsive.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4+-blue.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-orange.svg)

## ✨ Características

### 🎯 Funcionalidades Principales

- **Gestión Completa de Viajes**: Crea y administra múltiples viajes con información detallada
- **Itinerarios por Días**: Organiza actividades día a día con secciones personalizables
- **Mapas Interactivos**: Integración con Google Maps para ubicaciones precisas
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
  - Mapa interactivo con marcadores de ubicaciones
  - Actividades organizadas por secciones (Mañana, Tarde, Noche, etc.)
  - Detalles adicionales (horarios, precios, confirmaciones)
  - Enlaces directos a Google Maps

### 🔐 Panel de Administración

#### Gestión de Viajes
- Crear/editar/eliminar viajes
- Subida de imagen de portada
- Configuración de fechas y slug único
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
- Configurar centro y zoom del mapa
- Ordenar días mediante drag & drop
- Vista previa de cada día

#### Gestión de Secciones
- Organizar días en secciones (Mañana, Tarde, Noche, Opcional)
- Reordenar secciones con drag & drop

#### Gestión de Actividades
- Crear actividades con icono y descripción
- Asignar coordenadas GPS para visualizar en mapa
- Agregar detalles adicionales (horarios, precios, etc.)
- Selector visual de iconos Material Symbols
- Mover actividades entre secciones con drag & drop
- Reordenar dentro de cada sección

#### Información Adicional
- **Alojamientos**: Guarda información de hoteles/apartamentos con ubicación
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
- **Google Maps API**: Integración de mapas

### Características Técnicas
- **Drag & Drop nativo HTML5**: Sin librerías externas
- **Responsive Design**: Mobile-first approach
- **PWA-ready**: Optimizado para funcionar como app
- **AJAX**: Actualizaciones sin recargar página
- **Session Management**: Sistema de sesiones PHP

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
git clone https://github.com/tu-usuario/gestor-viajes.git
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

### 4. Configurar Google Maps API

1. Obtén una API Key de [Google Cloud Console](https://console.cloud.google.com/)
2. Habilita "Maps JavaScript API"
3. Inserta la key en la tabla `configuracion`:

```sql
INSERT INTO configuracion (clave, valor) 
VALUES ('google_maps_api_key', 'TU_API_KEY_AQUI')
ON DUPLICATE KEY UPDATE valor = 'TU_API_KEY_AQUI';
```

### 5. Configurar permisos

```bash
# Crear carpeta de imágenes y dar permisos de escritura
mkdir -p images
chmod 755 images
chown www-data:www-data images  # En producción
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
│   │   └── icon-picker.js    # Componente selector de iconos
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
│       └── portada/
├── index.php                 # Portada pública
├── menu.php                  # Menú de días del viaje
├── dia.php                   # Vista de día individual
├── gestor_viajes.sql         # Schema de la base de datos
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
**Solución**: 
1. Verifica que la API Key de Google Maps esté configurada
2. Comprueba que la API esté habilitada en Google Cloud Console
3. Revisa la consola del navegador para errores

### Drag & Drop no funciona en móvil
**Nota**: El drag & drop actual solo funciona con mouse. Para móviles se necesitaría implementar eventos touch adicionales.

## 📝 Uso

### Crear tu primer viaje

1. Accede al panel admin y haz login
2. Clic en "+ Nuevo Viaje"
3. Rellena información básica y sube una imagen de portada
4. Elige una plantilla de colores o personaliza manualmente
5. Guarda el viaje

### Agregar días al viaje

1. Desde el dashboard, clic en "Días" del viaje
2. "+ Nuevo Día" y configura fecha, título
3. Define el centro del mapa (lat/lng) y zoom
4. Opcionalmente crea secciones para organizar el día

### Agregar actividades

1. Entra en "Actividades" del día
2. "+ Nueva Actividad"
3. Selecciona icono, título, descripción
4. Añade coordenadas GPS para mostrar en mapa
5. Agrega detalles adicionales (horarios, precios)

### Publicar el viaje

El viaje es accesible públicamente en:
```
http://tu-dominio.com/?viaje=slug-del-viaje
```

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

- **Tu Nombre** - *Desarrollo inicial* - [tu-usuario](https://github.com/tu-usuario)

## 🙏 Agradecimientos

- [Google Maps API](https://developers.google.com/maps) - Mapas interactivos
- [Material Symbols](https://fonts.google.com/icons) - Iconos
- [TailwindCSS](https://tailwindcss.com/) - Framework CSS
- [PHP](https://www.php.net/) - Lenguaje backend

## 📧 Contacto

Para preguntas o sugerencias:
- Email: tu-email@ejemplo.com
- Issues: [GitHub Issues](https://github.com/tu-usuario/gestor-viajes/issues)

---

⭐ Si te gusta este proyecto, dale una estrella en GitHub!
