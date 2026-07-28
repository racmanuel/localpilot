# Mi cuenta → Mis entregas

## Alcance

Crear el endpoint nativo de WooCommerce para que el repartidor opere sus entregas desde el frontend, respetando el tema y componentes de Mi cuenta.

## Dependencias

- Roles/capacidades.
- Query de asignaciones.
- Servicio de transiciones.
- Mapbox para detalle.
- Evidencias para completar/fallar.
- Validación puntual de ubicación al completar, si está activa.
- Contratos de errores y estados.

## Endpoint

```text
mis-entregas
```

Registro mediante rewrite endpoint y flush solo en activación. La pestaña se muestra a drivers y gestores autorizados, pero el acceso directo también se valida.

## Vistas

### Listado

Filtros internos:

- Pendientes: `assigned`, `accepted`.
- En reparto: `out_for_delivery`.
- Entregadas: `delivered`.
- Fallidas: `failed`.
- Todas: incluye también `cancelled`.

El listado muestra un resumen con contadores por estado y filtros tipo pill con cantidades. La consulta conserva únicamente la asignación más reciente de cada pedido para el repartidor actual, evitando duplicados históricos de la misma asignación.

En escritorio se usa una tabla operativa con pedido, receptor, destino, fecha, estado y acción contextual. En móvil cada fila se convierte visualmente en una tarjeta apilada con botón de ancho completo.

Las direcciones se construyen solo con componentes no vacíos. Cuando no existe información se muestra `Dirección no disponible`, nunca una cadena formada únicamente por separadores.

Las acciones de la lista son contextuales: `Aceptar`, `Iniciar reparto`, `Completar` o `Ver entrega`; enlazan al detalle donde se ejecuta la transición protegida por nonce y permisos.

Los estados vacíos incluyen una explicación y una orientación para probar otro filtro.

### Detalle

La vista individual comparte tokens, badges e iconos Dashicons con el listado. Se aplica a entregas activas y cerradas, pero adapta las acciones al estado actual.

Jerarquía de contenido:

- encabezado con regreso al listado, número, fecha y estado;
- cliente y destino como bloque principal, con teléfono y total cuando la configuración lo permite;
- acciones rápidas para llamar, copiar la dirección y abrir navegación;
- mapa de referencia cuando existen token y coordenadas, con fallback textual y de navegación;
- productos como lista compacta con cantidad;
- prueba de entrega con receptor, notas, motivo, evidencia y resumen de validación puntual;
- acción siguiente para aceptar o iniciar;
- formulario principal para completar y panel secundario plegable para reportar fallo;
- timeline traducido con fecha y actor genérico, sin nombres técnicos ni JSON.

En estados terminales no se renderizan formularios de mutación. Se muestra un resumen de cierre y permanecen disponibles la prueba y la actividad registradas.

El formulario de completar conserva `multipart/form-data`, nonce y los campos `lclplt_assignment_id`, `lclplt_delivery_action`, `lclplt_received_by`, `lclplt_delivery_notes`, `lclplt_proof` y los campos privados de captura GPS. El panel de fallo conserva el selector `lclplt_failed_reason` y evidencia opcional.

La validación puntual utiliza clases semánticas `is-idle`, `is-requesting`, `is-success` e `is-error`; `aria-live` comunica el resultado. JavaScript mejora captura, reintento, copia y doble envío, pero no cambia autorizaciones ni transiciones.

Nunca mostrar datos financieros sensibles, notas internas ajenas a LocalPilot o pedidos de otro repartidor.

## Archivos a crear o tocar

- `includes/integrations/class-*-my-account.php`
- `includes/deliveries/class-*-delivery-query.php`
- `public/class-*-public.php`
- `public/partials/` o `templates/my-account/deliveries/`
- CSS/JS públicos existentes, con carga condicional
- controlador REST solo si la interacción asincrónica aporta valor

Templates sugeridos:

```text
templates/my-account/deliveries/list.php
templates/my-account/deliveries/detail.php
templates/my-account/deliveries/complete-form.php
templates/my-account/deliveries/fail-form.php
templates/my-account/deliveries/empty.php
```

Usar el localizador de templates existente o uno pequeño compatible con overrides; no copiar el sistema completo de WooCommerce.

## Tareas

1. Registrar endpoint y menú condicional.
2. Resolver rutas de listado/detalle sin confiar en el ID de URL.
3. Implementar query paginada por driver actual.
4. Renderizar con clases y helpers nativos de WooCommerce.
5. Usar `wc_add_notice()` para resultados.
6. Implementar aceptar/iniciar/completar/fallar mediante servicio.
7. Evitar double submit.
8. Integrar mapa de forma lazy/condicional.
9. Implementar estados vacíos y errores accesibles.
10. Probar con temas clásicos y de bloques dentro del alcance.
11. Capturar una sola posición al completar cuando la validación esté activa; mostrar reintento si el navegador deniega el permiso.
12. Mostrar contadores agrupados por estado sin contar dos veces asignaciones históricas del mismo pedido para el repartidor.
13. Hacer fallback de dirección con partes no vacías y texto explícito cuando falte.

## Criterios de aceptación

- La pestaña no aparece para clientes normales.
- Acceder a un `order_id` ajeno devuelve respuesta segura sin filtrar existencia.
- La lista pagina y conserva el filtro.
- La lista no duplica un pedido por asignaciones históricas repetidas del mismo repartidor.
- Los contadores coinciden con los registros visibles de cada filtro.
- Una dirección incompleta no produce `,` como único contenido.
- Cada estado ofrece solo acciones válidas.
- Dos pestañas no pueden completar dos veces la misma entrega.
- Formularios funcionan sin JavaScript; JS solo mejora experiencia.
- Los notices siguen el estilo de WooCommerce.
- La vista es usable a 320 px y con teclado.
- El detalle no desborda a 320 px; mapa, uploads, formularios, acciones y timeline permanecen legibles.
- `Completar entrega` es la acción principal en reparto y `Reportar fallo` permanece plegado hasta que el usuario lo abre.
- El historial usa etiquetas humanas compartidas con administración y nunca imprime `event_type`, `event_data` o coordenadas.
- Las entregas terminales no muestran formularios y conservan acceso a prueba e historial.
- El resumen y filtros son navegables por teclado y exponen el filtro actual con `aria-current`.
- Mapbox solo se carga en el detalle que tiene coordenadas/token.
- La ubicación se obtiene únicamente al completar; no se ejecuta `watchPosition()` ni tracking en segundo plano.
- El formulario funciona bajo HTTPS y muestra una alternativa explícita según la política `warning`/`blocking`.

## Riesgos

- Fuga de PII por IDOR: ownership en servidor para cada acción.
- Endpoint 404 tras activación: flush únicamente en activación/upgrade relevante.
- Conflicto de slug: endpoint filtrable antes de 1.0 si se requiere.
- Tema sobrescribe estilos: CSS específico mínimo, sin framework.
- URLs cacheadas: páginas de cuenta no deben cachearse de forma pública.

## Definición de terminado

Un repartidor autenticado completa el flujo asignado → aceptado → en reparto → entregado/fallido desde Mi cuenta, solo sobre sus pedidos, con interfaz nativa, responsive y degradación funcional sin JavaScript.
