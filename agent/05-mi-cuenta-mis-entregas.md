# Mi cuenta → Mis entregas

## Alcance

Crear el endpoint nativo de WooCommerce para que el repartidor opere sus entregas desde el frontend, respetando el tema y componentes de Mi cuenta.

## Dependencias

- Roles/capacidades.
- Query de asignaciones.
- Servicio de transiciones.
- Mapbox para detalle.
- Evidencias para completar/fallar.
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

Usar tabla responsive y paginación de WooCommerce. En móvil se permite CSS mínimo para lectura tipo card.

### Detalle

Mostrar solo lo necesario:

- pedido y productos;
- nombre, teléfono y dirección de envío;
- notas útiles para entrega;
- pago y monto a cobrar si configuración lo permite;
- estado y fechas;
- mapa;
- acciones válidas;
- evidencia/historial resumido cuando corresponda.

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

## Criterios de aceptación

- La pestaña no aparece para clientes normales.
- Acceder a un `order_id` ajeno devuelve respuesta segura sin filtrar existencia.
- La lista pagina y conserva el filtro.
- Cada estado ofrece solo acciones válidas.
- Dos pestañas no pueden completar dos veces la misma entrega.
- Formularios funcionan sin JavaScript; JS solo mejora experiencia.
- Los notices siguen el estilo de WooCommerce.
- La vista es usable a 320 px y con teclado.
- Mapbox solo se carga en el detalle que tiene coordenadas/token.

## Riesgos

- Fuga de PII por IDOR: ownership en servidor para cada acción.
- Endpoint 404 tras activación: flush únicamente en activación/upgrade relevante.
- Conflicto de slug: endpoint filtrable antes de 1.0 si se requiere.
- Tema sobrescribe estilos: CSS específico mínimo, sin framework.
- URLs cacheadas: páginas de cuenta no deben cachearse de forma pública.

## Definición de terminado

Un repartidor autenticado completa el flujo asignado → aceptado → en reparto → entregado/fallido desde Mi cuenta, solo sobre sus pedidos, con interfaz nativa, responsive y degradación funcional sin JavaScript.
