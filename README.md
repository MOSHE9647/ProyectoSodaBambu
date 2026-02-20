# Proyecto Soda Bambú

## Índice
- [📋 Descripción del Proyecto](#-descripción-del-proyecto)
- [🎯 Objetivos](#-objetivos) 
- [🏗️ Estructura del Proyecto](#️-estructura-del-proyecto)
- [📚 Documentación](#-documentación)
- [🔧 Tecnologías](#-tecnologías)
- [🚀 Instalación y Configuración](#-instalación-y-configuración)
- [🌿 Flujo de Trabajo con Git y Gitflow](#-flujo-de-trabajo-con-git-y-gitflow)
- [👥 Equipo de Desarrollo](#-equipo-de-desarrollo)
- [🏪 Sobre el Cliente](#-sobre-el-cliente)
- [⚖️ Derechos de Autor y Términos de Uso](#️-derechos-de-autor-y-términos-de-uso)

## 📋 Descripción del Proyecto

Este proyecto tiene como objetivo desarrollar un sistema para la gestión interna de la Soda y Restaurante El Bambú, con el fin de optimizar sus procesos administrativos, financieros y operativos. Esto proyecto se está desarrollando como parte del desarrollo de los cursos de Ingeniería en Sistemas I, II y III de la Universidad Nacional de Costa Rica.

## 🎯 Objetivos

- Desarrollar un sistema de gestión completo para el establecimiento Soda y Restaurante El Bambú.
- Implementar buenas prácticas de ingeniería de software.
- Aplicar metodologías de desarrollo de sistemas.

## 🏗️ Estructura del Proyecto

```
ProyectoSodaBambu/
├── assets/                            # Recursos estáticos
│   ├── icons/                         # Iconos del proyecto
│   └── images/                        # Imágenes y logos
├── docs/                              # Documentación del proyecto
│   ├── Documento de Casos de Uso.docx
│   ├── Documento de Especificación de Requisitos de Software.docx
│   ├── Documento de Reglas de Negocio.docx
│   ├── Documento de Visión y Alcance.docx
│   └── IngenieriaII/                  # Documentación específica del ciclo II
├── legal/                             # Documentos legales y contratos
│   ├── Carta de Intenciones (contrato).docx
│   ├── Carta de Intenciones (firmado).pdf
│   ├── Codigo de Ética (firmado).pdf
│   └── Código de Ética.docx
├── scripts/                           # Scripts de automatización
│   ├── database/                      # Scripts de base de datos
│   └── deployment/                    # Scripts de despliegue
├── source_code/                       # Código fuente Laravel
│   ├── api/                           # Configuración redireccionamiento para despliegue
│   ├── app/                           # Lógica de la aplicación
│   ├── config/                        # Archivos de configuración
│   ├── database/                      # Migraciones y seeders
|   ├── lang/                          # Archivos de idioma
│   ├── public/                        # Archivos públicos
│   ├── resources/                     # Vistas y assets
│   ├── routes/                        # Definición de rutas
│   └── .env.example                   # Plantilla de variables de entorno
├── uml/                               # Diagramas UML
│   ├── activity_diagrams/             # Diagramas de actividad
│   ├── class_diagrams/                # Diagramas de clases
│   ├── sequence_diagrams/             # Diagramas de secuencia
│   ├── state_diagrams/                # Diagramas de estado
│   └── use_case_diagrams/             # Diagramas de casos de uso
└── README.md                          # Este archivo
```

## 📚 Documentación

### Documentos de Análisis y Diseño
- **Documento de Visión y Alcance**: Define el propósito, objetivos y límites del proyecto
- **Documento de Especificación de Requisitos de Software**: Detalla los requisitos funcionales y no funcionales
- **Documento de Casos de Uso**: Describe las interacciones entre los usuarios y el sistema
- **Documento de Reglas de Negocio**: Establece las reglas y políticas del negocio

### Documentos Legales
- **Carta de Intenciones**: Acuerdo formal con el cliente
- **Código de Ética**: Principios éticos que rigen el desarrollo del proyecto

## 🔧 Tecnologías

### Backend
- **PHP**: >= 8.2
- **Framework**: Laravel ^12.x (PHP)
- **Base de datos**: MySQL/SQLite
- **Autenticación**: Laravel Fortify

### Frontend
- **Templates**: Laravel Blade
- **CSS Framework**: Bootstrap 5
- **JavaScript**: Vanilla JS + jQuery

### Deployment
- **Hosting de la Aplicación**: Vercel
- **Servidor de Base de Datos**: TiDB Cloud

### Herramientas de Desarrollo
- **Control de versiones**: Git + Gitflow
- **Gestor de dependencias PHP**: Composer
- **Gestor de dependencias JS**: npm
- **Entorno de desarrollo**: XAMPP / Laragon
- **IDE recomendado**: Visual Studio Code / PHPStorm

## 🚀 Instalación y Configuración

### Requisitos Previos
- PHP >= 8.2
- Composer
- Node.js y NPM
- MySQL/SQLite
- Git

### Pasos de Instalación

1. **Clonar el repositorio**
   ```bash
   git clone [URL-del-repositorio]
   cd ProyectoSodaBambu
   ```

2. **Configurar Laravel**
   ```bash
   cd source_code
   ```

   Para configurar Laravel para que trabaje con **SQLite**, ejecuta el siguiente comando:

   ```bash
   composer workspace:sqlite
   ```

   Si, en cambio, deseas trabajar con MySQL, ejecuta el siguiente comando cambiando cada uno de los valores usados por las credenciales de tu base de datos:
   
   > **Nota:** Antes de continuar, asegúrate de que el servidor de tu base de datos (XAMPP/Laragon) esté configurado correctamente y en ejecución.

   ```bash
   composer workspace:mysql -- --db-host 127.0.0.1 --db-port 3306 --db-name laravel_db --db-username root --db-password su_contrasena
   ```

   Al final, con los datos asignados, el archivo ```.env``` debería verse algo así:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=nombre_base_datos
   DB_USERNAME=tu_usuario
   DB_PASSWORD=tu_contraseña
   ```

   Si utilizaste los valores por defecto, el archivo ```.env``` debería verse así:
   ```
   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=laravel_db
   DB_USERNAME=root
   DB_PASSWORD=
   ```

   > **Nota:** También puedes omitir algunos de los argumentos del comando anterior si desea que estos se queden con su valor por defecto.

   El siguiente comando utiliza los valores por defecto mostrados en el comando anterior:

   ```bash
   composer workspace:mysql
   ```

   Estos comandos crearán y actualizarán el archivo ```.env``` con los datos necesarios para conectarse a cualquiera de las dos Bases de Datos. Cabe aclarar que también puedes configurar el proyecto manualmente si así lo deseas.

3. **Iniciar el servidor de desarrollo**
   ```bash
   composer run dev
   ```

4. **Acceder a la aplicación**
   
   Abre tu navegador en: `http://localhost:8000`

Como información adicional, si deseas ejecutar los *seeders* existentes en el proyecto puedes utilizar los comandos ```composer workspace:[db]:seed``` o ```composer workspace:[db]:fresh:seed``` para realizarlo utilizando los datos existentes o borrando todos los datos existentes, respectivamente. Solamente cambia ```[db]``` por el nombre de tu motor de base de datos según lo mencionado en pasos anteriores.

### Scripts Disponibles

Revisa la carpeta [scripts/](scripts/) para:
- **scripts/database/**: Scripts de base de datos (backups)
- **scripts/deployment/**: Scripts de despliegue y configuración de producción

## 🌿 Flujo de Trabajo con Git y Gitflow

### Configuración Inicial de Gitflow

Si es tu primera vez trabajando en el proyecto, configura Gitflow:

```bash
git flow init
```

- **Rama de producción**: `main`
- **Rama de desarrollo**: `dev`
- Acepta los nombres por defecto para features, releases y hotfixes

### Comandos Principales

#### 1. Trabajar en una Nueva Funcionalidad (Feature)

```bash
# Crear una nueva feature
git flow feature start nombre-de-la-feature

# Trabajar en tus cambios
git add .
git commit -m "Descripción del cambio"

# Subir la feature a GitHub
git push origin feature/nombre-de-la-feature
```

**Importante**: Crea un **Pull Request** en GitHub de `feature/nombre-de-la-feature` → `dev`

#### 2. Lanzar una Nueva Versión (Release)

```bash
# Iniciar una release
git flow release start v1.0.0

# Hacer ajustes finales (versiones, documentación, etc.)
git add .
git commit -m "Preparar release v1.0.0"

# Subir y crear PR
git push origin release/v1.0.0
```

Crea Pull Requests de `release/v1.0.0` → `main` y `dev`

#### 3. Correcciones Urgentes En Main (Hotfix)

```bash
# Iniciar un hotfix desde main
git flow hotfix start fix-descripcion

# Hacer correcciones
git add .
git commit -m "Fix: descripción del problema"

# Subir y crear PR
git push origin hotfix/fix-descripcion
```

Crea Pull Requests de `hotfix/fix-descripcion` → `main` y `dev`

### Reglas de Oro del Proyecto

1. ✅ **SIEMPRE** trabaja en una rama feature/release/hotfix
2. ❌ **NUNCA** hagas push directo a `main` o `dev`
3. ✅ **TODOS** los cambios deben pasar por Pull Request
4. ✅ **ESPERA** la aprobación del PR antes de hacer merge
5. ✅ Mantén tu rama actualizada con `dev`:
   ```bash
   git checkout dev
   git pull origin dev
   git checkout feature/tu-feature
   git merge dev
   ```

### Convenciones de Commits

Usa mensajes descriptivos en **inglés**:

```bash
# Buenos ejemplos
git commit -m "Add user authentication"
git commit -m "Fix total calculation error"
git commit -m "Update installation documentation"

# Evitar
git commit -m "fix"
git commit -m "cambios"
git commit -m "update"
```

### Eliminación de Ramas

Después de hacer `merge` a un PR, elimina la rama:

```bash
# Eliminar localmente
git branch -d feature/nombre-de-la-feature

# Eliminar en GitHub
git push origin --delete feature/nombre-de-la-feature
```

### Comandos Útiles de Git

```bash
# Ver estado actual
git status

# Ver ramas locales
git branch

# Ver ramas remotas
git branch -r

# Cambiar de rama
git checkout nombre-rama

# Actualizar desde remoto
git pull origin nombre-rama

# Ver historial de commits
git log --oneline --graph --all

# Deshacer cambios no commiteados
git checkout -- .

# Ver diferencias
git diff
```

### Resolución de Conflictos

Si encuentras conflictos al hacer merge:

1. Abre los archivos en conflicto en VS Code
2. Resuelve los conflictos manualmente (VS Code te ayudará)
3. Marca como resuelto:
   ```bash
   git add .
   git commit -m "Resolver conflictos de merge"
   ```

## 👥 Equipo de Desarrollo

### 🎓 Estudiantes Desarrolladores
| Nombre | Correo Institucional | GitHub |
|--------|---------------------|---------|
| Isaac Herrera Pastrana | isaac.herrera.pastrana@est.una.ac.cr | [@Moshe9647](https://github.com/Moshe9647) |
| Melanie Oviedo Maleaño | melanie.oviedo.maleano@est.una.ac.cr | [@MelanieOviedo](https://github.com/MelanieOviedo) |
| Natalia Ortiz Martinez | deyaneira.ortiz.martinez@est.una.ac.cr | [@DeyaneiraOrtizMartinez](https://github.com/DeyaneiraOrtizMartinez) |
| Andrea Morera Zúñiga | andrea.morera.zuniga@est.una.ac.cr | [@AndreMoreZu](https://github.com/AndreMoreZu) |
| Jeremy Romero Carazo | jeremy.romero.carazo@est.una.ac.cr | [@Romero42](https://github.com/Romero42) |

### 👨‍🏫 Supervisión Académica
- **Ingeniería en Sistemas I**: M.Sc. Olivier Blanco Sandí
- **Ingeniería en Sistemas II**: Prof. Adán Carranza Alfaro
- **Ingeniería en Sistemas III**: Prof. Michael Barquero Salazar

## 🏪 Sobre el Cliente

<img src="assets/images/logo-bambu.png" alt="Logo El Bambú" align="left" width="90"/>

**Soda y Restaurante El Bambú** es un negocio ubicado en Cariari, Pococí, Limón, Costa Rica. Su principal objetivo es ofrecer un servicio de comidas, con una variada selección de platillos y bebidas tipo soda, con un enfoque en ofrecer un servicio accesible, rápido y de calidad a la comunidad.

### 🔍 Problemática Actual
A pesar de ser una empresa pequeña, el restaurante enfrenta limitaciones debido a la falta de digitalización en sus procesos internos. Actualmente el control de ventas, los registros financieros, el cálculo de salarios, la gestión de inventario, las reservas y los reportes operativos se realizan de forma manual, lo que reduce la eficiencia del negocio.

### 🎯 Objetivo del Sistema
Con la implementación del sistema, se busca mejorar la organización interna, optimizar recursos y facilitar una toma de decisiones basada en datos reales.

## ⚖️ Derechos de Autor y Términos de Uso

### 🎓 Proyecto Académico
Este sistema fue desarrollado como proyecto académico del curso de **Ingeniería en Sistemas I, II y III** de la Universidad Nacional de Costa Rica durante el **I y II Ciclo 2025** y el **I Ciclo 2026**.

### 👥 Autoría y Desarrollo
- **Desarrollado por**: Equipo de estudiantes de Ingeniería en Sistemas (ver sección "Equipo de Desarrollo").
- **Supervisión académica**: M.Sc. Olivier Blanco Sandí (Ingeniería I), Prof. Adán Carranza Alfaro (Ingeniería II) y Prof. Prof. Michael Barquero Salazar (Ingeniería III).
- **Institución**: Universidad Nacional de Costa Rica, Sección Regional Huetar Norte y Caribe.
- **Período actual**: II Ciclo 2025 (Ingeniería en Sistemas II).
- **Proyecto completo**: I y II Ciclo 2025, I Ciclo 2026 (Ingeniería I, II y III).

### 🏪 Cliente y Propósito
- **Cliente**: Soda y Restaurante El Bambú.
- **Ubicación**: Cariari, Pococí, Limón, Costa Rica.
- **Propósito**: Sistema de gestión integral para digitalizar y optimizar los procesos del establecimiento.
- **Derechos de uso comercial**: Según lo establecido en la Carta de Intenciones firmada.

### 📋 Términos de Uso
1. **Uso Académico**: Este proyecto puede ser referenciado con fines académicos citando apropiadamente la autoría y la institución.
2. **Uso Comercial**: Los derechos de uso comercial del sistema pertenecen a Soda y Restaurante El Bambú según el acuerdo establecido en los documentos legales del proyecto.
3. **Modificaciones**: Cualquier modificación al sistema debe ser autorizada por los autores originales y/o el cliente.
4. **Distribución**: La distribución del código está restringida según los términos del acuerdo con el cliente.
5. **Código Fuente**: El acceso al código fuente está limitado a fines académicos y de mantenimiento autorizado.

### 📞 Contacto para Consultas sobre Derechos
Para consultas sobre el uso de este software o derechos de autor:
- **Equipo de desarrollo**: Ver tabla de contactos en la sección "Equipo de Desarrollo"
- **Supervisión académica**: A través de los canales oficiales de la Universidad Nacional de Costa Rica

**© 2025 - Equipo de Desarrollo, Universidad Nacional de Costa Rica. Todos los derechos reservados.**

---

<img src="assets/images/logo-una.png" alt="Logo Universidad Nacional de Costa Rica" align="left" width="85"/>

**Universidad Nacional de Costa Rica**  
**Escuela de Informática**  
**Ingeniería en Sistemas II**  
**II Ciclo 2025**