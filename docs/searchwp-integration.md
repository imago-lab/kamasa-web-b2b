# Integración de SearchWP en Kamasa B2B

Este documento describe cómo integrar SearchWP con el formulario de búsqueda principal del tema hijo `kamasa-b2b-theme` y cómo configurar un motor de búsqueda optimizado para productos B2B.

## Formulario de búsqueda principal

Incluye el template `template-parts/header/header-search.php` dentro de tu `header.php` (o reemplaza el marcado existente) para aprovechar el motor de SearchWP llamado `productos`:

```php
<?php get_template_part( 'template-parts/header/header-search' ); ?>
```

El archivo [`template-parts/header/header-search.php`](../kamasa-b2b-theme/template-parts/header/header-search.php) mantiene el campo estándar `name="s"` requerido por WordPress/SearchWP y añade un campo oculto `engine` con el slug del motor que debe ejecutar SearchWP (`productos`). También conserva `post_type="product"` para que, si SearchWP no está disponible, WordPress limite la búsqueda a productos.

Si necesitas otro motor, filtra el slug mediante `kamasa_b2b_searchwp_engine_slug` en `functions.php`:

```php
add_filter( 'kamasa_b2b_searchwp_engine_slug', function() {
    return 'default';
} );
```

## Configuración recomendada en SearchWP

1. **Accede a** `Ajustes → SearchWP → Engines`.
2. **Motor**: edita el motor "Default" o crea uno nuevo llamado **"Productos"**. El slug se utilizará en el campo oculto `engine` del formulario.
3. **Fuentes (Sources)**: selecciona solo `Productos` (`product`) para dedicar el motor a WooCommerce. Desmarca `Entradas`, `Páginas` u otras fuentes.
4. **Ajustes de la fuente `Productos`**:
   - **Título**: asigna un peso alto, por ejemplo `10`.
   - **Contenido (Descripción larga)**: peso medio-bajo, como `3`.
   - **Extracto (Descripción corta)**: peso medio, por ejemplo `5`.
   - **Slug**: peso medio, alrededor de `5` para beneficiar coincidencias por código interno.
5. **Campos personalizados**:
   - Añade el meta campo `_sku` con peso muy alto (`15`–`20`) para priorizar coincidencias por código interno.
   - Incluye otros campos B2B relevantes (por ejemplo códigos alternativos) si existen, asignándoles un peso acorde.
6. **Taxonomías**: en la sección correspondiente agrega:
   - `pa_marca`, `pa_voltaje` u otros atributos críticos: peso alto (`8`–`10`).
   - Categorías de producto: peso medio/alto (`7`).
   - Etiquetas de producto: peso bajo (`2`).
7. **Palabras clave (Synonyms)**: define sinónimos útiles (ej. "destornillador" ↔ "desarmador") para abarcar terminología regional.
8. **Guardar y reindexar**: guarda el motor y, en la pestaña **Advanced**, ejecuta **Rebuild Index** para aplicar los cambios. Repite cada vez que ajustes pesos o campos.

## Plantilla de resultados (`search.php`)

El archivo [`kamasa-b2b-theme/search.php`](../kamasa-b2b-theme/search.php) implementa:

- Título dinámico con la consulta buscada.
- Loop estándar de WordPress que prioriza productos usando `wc_get_template_part( 'content', 'product' )` para mantener la presentación B2B.
- Resultados genéricos (entradas, páginas) con título y extracto cuando aparezcan.
- Mensaje de "No se encontraron resultados" y paginación con `the_posts_pagination()`.

Asegúrate de que el tema cargue esta plantilla en búsquedas y que la vista de producto muestre los precios/condiciones B2B adecuados.

## Reindexaciones y pruebas

Después de realizar cambios:

1. Ejecuta **Rebuild Index** en SearchWP.
2. Prueba búsquedas por SKU, marca y código interno para confirmar que los pesos priorizan correctamente.
3. Ajusta pesos según el feedback del equipo comercial.

