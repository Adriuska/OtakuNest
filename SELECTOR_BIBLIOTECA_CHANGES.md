# OtakuNest - Cambios Realizados - Selector de Biblioteca

## Cambios Implementados

### 1. ✅ Botón "Ver detalles" Eliminado
**Archivo:** `templates/anime/list.html.twig`

**Cambios:**
- Eliminado botón "Ver detalles" de las tarjetas de anime
- Reemplazado con botón "Añadir a biblioteca" directo en la tarjeta
- El botón abre un modal para seleccionar la biblioteca

**Antes:**
```twig
<button class="btn btn-sm btn-outline-warning w-100 anime-details-btn" 
        data-anime-id="{{ anime.id }}" 
        data-bs-toggle="modal" 
        data-bs-target="#animeDetailModal">
    <i class="fa-solid fa-eye"></i> Ver detalles
</button>
```

**Después:**
```twig
<button class="btn btn-sm btn-warning w-100" 
        data-anime-id="{{ anime.id }}" 
        data-anime-title="{{ anime.title }}" 
        data-anime-image="{{ anime.image }}" 
        data-bs-toggle="modal" 
        data-bs-target="#librarySelectModal">
    <i class="fa-solid fa-plus"></i> Añadir a biblioteca
</button>
```

---

### 2. ✅ Nuevo Modal para Seleccionar Biblioteca
**Archivo:** `templates/anime/list.html.twig`

**Características:**
- Modal ID: `librarySelectModal`
- Carga dinámicamente las bibliotecas del usuario desde API
- Muestra lista de bibliotecas con cantidad de animes
- Onclick agrega el anime a la biblioteca seleccionada

**HTML:**
```twig
<div class="modal fade" id="librarySelectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark border-secondary">
            <div class="modal-header border-secondary">
                <h5 class="modal-title">Seleccionar biblioteca</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="librarySelectContent">
                    <!-- Se carga dinámicamente con JavaScript -->
                </div>
            </div>
        </div>
    </div>
</div>
```

---

### 3. ✅ JavaScript Actualizado para Manejo de Biblioteca
**Archivo:** `templates/anime/list.html.twig`

**Funciones Nuevas:**

#### `loadUserLibraries(animeId, animeTitle, animeImage)`
- Obtiene las bibliotecas del usuario desde `/api/user/libraries`
- Guarda datos del anime en variable global `currentAnimeData`
- Renderiza lista de bibliotecas disponibles

#### `renderLibrarySelect(libraries)`
- Crea botones para cada biblioteca
- Muestra nombre y cantidad de animes
- Añade listeners para agregar anime a biblioteca

#### `addAnimeToLibrary(libraryId)`
- Hace POST a `/anime/api/add-library` con `library_id`
- Cierra modal automáticamente
- Muestra mensaje de éxito

**Flujo:**
1. Usuario clickea "Añadir a biblioteca"
2. Se abre modal con spinner
3. Se cargan bibliotecas del usuario
4. Usuario selecciona una biblioteca
5. Anime se agrega a esa biblioteca
6. Modal se cierra automáticamente

---

### 4. ✅ Nuevo Endpoint de API: `/api/user/libraries`
**Archivo:** `src/Controller/ApiController.php` (NUEVO)

**Ruta:** `GET /api/user/libraries`

**Respuesta JSON:**
```json
{
    "success": true,
    "libraries": [
        {
            "id": 1,
            "name": "Mis Favoritos",
            "items": 15
        },
        {
            "id": 2,
            "name": "Viendo",
            "items": 8
        }
    ]
}
```

**Validación:**
- Requiere usuario autenticado
- Solo obtiene bibliotecas del usuario actual
- Retorna 401 si no está autenticado

---

### 5. ✅ Endpoint Modificado: `/anime/api/add-library`
**Archivo:** `src/Controller/AnimeController.php`

**Cambios:**
- Ahora acepta `library_id` opcional en el request
- Si se envía `library_id`, agrega a esa biblioteca específica
- Si no se envía, usa el comportamiento anterior (primera biblioteca o crea una)
- Verifica que la biblioteca pertenezca al usuario

**Request JSON:**
```json
{
    "anime_id": 123,
    "title": "Attack on Titan",
    "image": "https://...",
    "library_id": 2
}
```

**Respuesta:**
```json
{
    "success": true,
    "message": "Attack on Titan añadido a Viendo"
}
```

---

## Archivos Modificados

### 1. `templates/anime/list.html.twig`
- ✅ Eliminado botón "Ver detalles"
- ✅ Agregado botón "Añadir a biblioteca"
- ✅ Nuevo modal `#librarySelectModal`
- ✅ JavaScript para cargar y seleccionar bibliotecas
- ✅ Eliminado código obsoleto del modal de detalles

### 2. `src/Controller/AnimeController.php`
- ✅ Modificado método `addLibrary()` para aceptar `library_id`
- ✅ Validación de propiedad de biblioteca

### 3. `src/Controller/ApiController.php` (NUEVO)
- ✅ Creado controlador de API
- ✅ Endpoint `/api/user/libraries` para obtener bibliotecas del usuario

---

## Flujo de Usuario

### Antes (Sin Cambios):
1. Usuario ve tarjeta de anime
2. Clickea "Ver detalles" → abre modal con info
3. Clickea "Añadir a biblioteca" en el modal
4. Se agrega a primera biblioteca (o crea una)

### Después (Con Cambios):
1. Usuario ve tarjeta de anime
2. Clickea "Añadir a biblioteca" → abre modal de selector
3. Modal muestra sus bibliotecas
4. Usuario elige biblioteca
5. Anime se agrega a esa biblioteca

**Ventajas:**
- ✅ Menos clicks (directo sin ver detalles)
- ✅ Usuario elige biblioteca
- ✅ UX más intuitivo
- ✅ Más rápido agregar a biblioteca

---

## Testing

### Casos de Prueba:

1. **Sin autenticación:**
   - ❌ API `/api/user/libraries` retorna 401
   - ❌ No se puede agregar a biblioteca

2. **Usuarios sin bibliotecas:**
   - ✅ Modal muestra "No tienes bibliotecas"
   - ✅ Ofrece enlace para crear una

3. **Usuario autenticado:**
   - ✅ Modal carga bibliotecas correctamente
   - ✅ Seleccionar biblioteca agrega anime
   - ✅ Mensaje de éxito personalizado con nombre de biblioteca

4. **Múltiples bibliotecas:**
   - ✅ Todas aparecen en la lista
   - ✅ Anime se agrega a la seleccionada
   - ✅ Contador de animes se actualiza

---

## Compatibilidad

- ✅ Requiere Bootstrap 5.3+
- ✅ Requiere moderna API Fetch
- ✅ Funciona en todos los navegadores modernos
- ✅ Sin dependencias adicionales

---

## Notas de Desarrollo

- El endpoint `/anime/api/add-library` sigue siendo compatible con el antiguo formato (sin `library_id`)
- Se puede hacer más llamadas sin refreshear la página
- El caché de anime detalles se removió (no necesario)
- Todo el flujo es asincrónico y no bloquea la UI

---

## Próximos Pasos (Opcionales)

1. Agregar animación al cambio de biblioteca
2. Mostrar notificación tipo toast en lugar de alert()
3. Agregar opción para crear biblioteca directamente desde el modal
4. Guardar última biblioteca seleccionada
5. Validar que no haya duplicados en biblioteca
