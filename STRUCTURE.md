# 📁 Estructura Completa del Proyecto

## 🎯 Estructura Recomendada para GitHub

```
gestor-viajes/
│
├── 📂 admin/                          # Panel de administración
│   ├── 📂 js/                         # JavaScript del admin
│   │   ├── icons-library.js           # Librería de iconos Material Symbols
│   │   └── icon-picker.js             # Componente selector de iconos
│   │
│   ├── 📂 partials/                   # Componentes reutilizables PHP
│   │   └── actividad_item.php         # Item de actividad para listado
│   │
│   ├── actividad_delete.php           # Eliminar actividad
│   ├── actividad_form.php             # Crear/editar actividad
│   ├── actividades_list.php           # Listado de actividades
│   ├── alojamiento_delete.php         # Eliminar alojamiento
│   ├── alojamiento_form.php           # Crear/editar alojamiento
│   ├── alojamientos_list.php          # Listado de alojamientos
│   ├── auth.php                       # Sistema de autenticación
│   ├── cambiar_password.php           # Cambiar contraseña admin
│   ├── contactos_form.php             # Gestión de contactos emergencia
│   ├── contactos_list.php             # Listado de contactos
│   ├── dia_delete.php                 # Eliminar día
│   ├── dia_form.php                   # Crear/editar día
│   ├── dias_list.php                  # Listado de días del viaje
│   ├── index.php                      # Dashboard principal admin
│   ├── login.php                      # Login del administrador
│   ├── logout.php                     # Cerrar sesión
│   ├── seccion_form.php               # Crear/editar sección
│   ├── secciones_list.php             # Listado de secciones
│   ├── viaje_delete.php               # Eliminar viaje
│   └── viaje_form.php                 # Crear/editar viaje
│
├── 📂 config/                         # Configuración
│   └── database.php                   # Conexión a base de datos
│
├── 📂 database/                       # Scripts SQL y migraciones
│   ├── 📂 migrations/                 # Migraciones futuras
│   │   └── .gitkeep                   # Mantener carpeta en Git
│   ├── schema.sql                     # Estructura de la BD
│   ├── seed.sql                       # Datos de ejemplo
│   └── README.md                      # Documentación de BD
│
├── 📂 images/                         # Imágenes subidas (Git ignore)
│   ├── .gitkeep                       # Mantener carpeta en Git
│   └── [viaje_id]/                    # Carpetas por viaje
│       └── portada/                   # Imágenes de portada
│
├── 📄 dia.php                         # Vista pública de día individual
├── 📄 index.php                       # Portada pública del viaje
├── 📄 menu.php                        # Menú de días del viaje
│
├── 📄 .gitignore                      # Archivos ignorados por Git
├── 📄 README.md                       # Documentación principal
├── 📄 MIGRATION_GUIDE.md              # Guía de migración a JS externos
├── 📄 QUICK_START.md                  # Guía rápida de inicio
├── 📄 STRUCTURE.md                    # Este archivo
└── 📄 LICENSE                         # Licencia del proyecto (opcional)
```

## 📦 Descripción de Carpetas

### `admin/` - Panel de Administración
Contiene toda la interfaz de gestión del sistema. Solo accesible con login.

**Subcarpetas:**
- `js/` - JavaScript modular y reutilizable
- `partials/` - Componentes PHP reutilizables

**Características:**
- Sistema de autenticación
- CRUD completo de viajes, días, actividades
- Gestión de alojamientos y contactos
- Drag & drop para reordenar
- Selector visual de iconos

### `config/` - Configuración
Configuraciones del sistema y conexión a base de datos.

**⚠️ Importante:** 
- `database.php` debe estar en `.gitignore`
- Usar variables de entorno en producción

### `database/` - Base de Datos
Scripts SQL organizados y versionados.

**Archivos:**
- `schema.sql` - Solo estructura (CREATE TABLE)
- `seed.sql` - Solo datos de ejemplo (INSERT)
- `migrations/` - Actualizaciones futuras del schema

### `images/` - Archivos Multimedia
Imágenes subidas por usuarios organizadas por viaje.

**⚠️ Importante:**
- Esta carpeta está en `.gitignore`
- Solo se guarda `.gitkeep` para crear la estructura
- En producción, configurar permisos de escritura (755)

### Raíz - Área Pública
Archivos accesibles públicamente sin login.

## 🔐 Archivos Sensibles (NO en Git)

Estos archivos **NO** deben subirse a GitHub:

```
❌ config/database.php              # Contiene credenciales
❌ images/*/                         # Imágenes de usuarios
❌ *.log                             # Logs del sistema
❌ backup_*.sql                      # Backups de BD
❌ .env                              # Variables de entorno
```

## ✅ Archivos que SÍ van en Git

```
✅ database/schema.sql               # Estructura de BD
✅ database/seed.sql                 # Datos de ejemplo
✅ admin/js/*.js                     # JavaScript
✅ admin/*.php                       # Código PHP
✅ README.md                         # Documentación
✅ .gitignore                        # Configuración de Git
```

## 📝 Archivos .gitkeep

Los archivos `.gitkeep` son archivos vacíos que se usan para mantener carpetas vacías en Git:

```bash
# Crear .gitkeep en carpetas que deben existir
touch images/.gitkeep
touch database/migrations/.gitkeep
```

## 🚀 Setup Inicial

### 1. Clonar el Repositorio
```bash
git clone https://github.com/Metehojorito/routes.git
cd gestor-viajes
```

### 2. Crear Carpetas Necesarias
```bash
mkdir -p images
mkdir -p database/migrations
chmod 755 images
```

### 3. Configurar Base de Datos
```bash
# Copiar ejemplo de configuración
cp config/database.example.php config/database.php

# Editar con tus credenciales
nano config/database.php
```

### 4. Importar SQL
```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
```

## 🔄 Workflow de Desarrollo

### Antes de Commitear

```bash
# Verificar que no subes archivos sensibles
git status

# Revisar cambios
git diff

# Agregar solo lo necesario
git add admin/
git add database/schema.sql
git add README.md

# Commit
git commit -m "feat: agregar nueva funcionalidad"

# Push
git push origin main
```

### Estructura de Commits

Usa [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: nueva característica
fix: corrección de bug
docs: documentación
style: formateo de código
refactor: refactorización
test: pruebas
chore: tareas de mantenimiento
```

## 📊 Tamaños Aproximados

```
admin/          ~500 KB   (PHP + JS)
config/         ~5 KB     (Configuración)
database/       ~100 KB   (SQL)
images/         Variable  (Depende de uso)
docs/           ~50 KB    (Markdown)
```

## 🔗 Enlaces Importantes

- **Repositorio**: https://github.com/Metehojorito/routes
- **Issues**: https://github.com/Metehojorito/routes/issues
- **Wiki**: https://github.com/Metehojorito/routes/wiki
- **Releases**: https://github.com/Metehojorito/routes/releases

## 🏷️ Versionado

Sigue [Semantic Versioning](https://semver.org/):

```
v1.0.0 - Release inicial
v1.1.0 - Nuevas características
v1.1.1 - Correcciones de bugs
v2.0.0 - Cambios breaking
```

## 📝 Changelog

Mantén un archivo `CHANGELOG.md` con los cambios de cada versión:

```markdown
## [1.0.0] - 2024-12-13
### Added
- Sistema de gestión de viajes
- Selector de iconos con JS externos
- 8 plantillas de colores

### Changed
- Migración a arquitectura JS modular

### Fixed
- Bug en drag & drop de actividades
```

## 🤝 Contribuciones

### Estructura de Pull Requests

```
1. Fork del proyecto
2. Crear rama: git checkout -b feature/nueva-caracteristica
3. Commit: git commit -m 'feat: agregar nueva característica'
4. Push: git push origin feature/nueva-caracteristica
5. Abrir Pull Request
```

---

Para más información, consulta:
- [README.md](README.md) - Documentación general
- [database/README.md](database/README.md) - Documentación de BD
- [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md) - Guía de migración