# Integración nativa con pedidos de WooCommerce

## Alcance

Extender las pantallas nativas de pedidos, tanto HPOS como almacenamiento heredado durante el periodo soportado, sin crear un tablero o listado paralelo en el MVP.

## Dependencias

- Repositorios y metadatos.
- `Assignment_Service` y `Delivery_Transition_Service`.
- Rol/capacidad `lclplt_manage_deliveries`.
- Contratos de estados y errores.

## Experiencia administrativa

### Lista de pedidos

Añadir, donde las APIs soportadas lo permitan:

- columna **Repartidor**;
- columna **Entrega**;
- columna **Ubicación**;
- filtros por repartidor y estado de entrega;
- acciones de fila para asignar/reasignar/abrir;
- acciones masivas acotadas.

Evitar convertir la lista en un dashboard. La información debe ser breve y accesible.

### Editor del pedido

Agregar panel **LocalPilot** con:

- repartidor y estado;
- fechas principales;
- estado de geocodificación;
- receptor y evidencia;
- últimos eventos;
- asignar, reasignar, retirar;
- reintentar geocodificación y editar ubicación;
- enlaces a evidencia.

Las acciones destructivas o de cambio de estado requieren confirmación apropiada y validación en servidor.

### Historial visible

Cada acción relevante agrega una nota privada al pedido. La tabla de eventos conserva la versión estructurada.

## Archivos a crear o tocar

- `admin/class-*-admin.php` para hooks/adaptación fina.
- `admin/partials/*-order-delivery-panel.php`
- `includes/integrations/class-*-orders-list.php`
- `includes/integrations/class-*-order-editor.php`
- `includes/deliveries/class-*-assignment-service.php`
- `includes/deliveries/class-*-transition-service.php`
- assets admin existentes, solo si son necesarios y con carga condicional.

No editar directamente el bootstrap; entregar el registro de hooks al integrador.

## Tareas

1. Detectar la pantalla de pedidos soportada sin depender de `shop_order` como post.
2. Implementar columnas y contenido en HPOS.
3. Mantener compatibilidad heredada solo donde el rango soportado lo exija.
4. Implementar filtros sin consultas directas a tablas de pedidos.
5. Crear panel del pedido con escaping y nonces.
6. Resolver asignación mediante el servicio compartido.
7. Implementar reasignación cerrando acceso anterior.
8. Añadir notas privadas traducibles.
9. Mostrar notices nativos de éxito/error.
10. Cargar SelectWoo/JS únicamente en pantallas relevantes.

## Reglas

- Solo usuarios con `lclplt_manage_deliveries`.
- No completar/fallar desde acciones masivas en el MVP.
- Un pedido no elegible no puede asignarse aunque la UI muestre datos obsoletos.
- El selector solo lista repartidores activos.
- La reasignación invalida inmediatamente al repartidor anterior.
- La UI no implementa reglas de transición; pregunta al servicio.

## Criterios de aceptación

- Las columnas aparecen en la lista HPOS sin romper ordenación o paginación.
- Los filtros no exponen pedidos indebidos ni degradan toda la lista.
- El panel carga y guarda en el editor HPOS.
- Asignar y reasignar actualiza tabla, meta, evento y nota.
- Retirar asignación elimina el acceso del conductor.
- Un request repetido no genera dos asignaciones activas.
- Los errores muestran notices nativos y conservan el pedido consistente.
- No hay un menú principal ni un listado LocalPilot duplicado.

## Riesgos

- Hooks distintos entre editores: encapsular adaptadores por pantalla.
- Consultas de filtros HPOS: usar APIs actuales y probar volúmenes razonables.
- Conflicto con plugins de columnas: nombres/IDs únicos y prioridades prudentes.
- Doble clic: idempotencia lógica y bloqueo visual secundario.

## Definición de terminado

El administrador realiza toda la operación del MVP desde Pedidos y el editor del pedido, con controles nativos, servicios compartidos, permisos correctos y paridad funcional bajo HPOS.
