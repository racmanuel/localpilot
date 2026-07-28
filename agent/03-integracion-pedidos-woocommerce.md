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

- resumen de estado, repartidor y validación puntual;
- línea de progreso con las fechas principales;
- prueba de entrega: receptor, motivo, notas y evidencia;
- gestión contextual para asignar, reasignar o retirar;
- mapa de auditoría con destino, GPS de entrega y radio histórico;
- timeline de eventos con etiquetas traducidas, fecha y actor;
- enlaces accesibles a evidencia y navegación externa.

Las acciones destructivas o de cambio de estado requieren confirmación apropiada y validación en servidor.

Los estados `delivered`, `failed` y `cancelled` son de solo lectura en el panel. La UI oculta las acciones de asignación y corrección del destino, y el handler rechaza también un POST manipulado.

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
11. Mantener el panel responsive a 320 px, operable con teclado y sin depender del color para comunicar estados.
12. Obtener los últimos eventos en orden cronológico y traducir sus tipos técnicos mediante un helper central.

## Reglas

- Solo usuarios con `lclplt_manage_deliveries`.
- No completar/fallar desde acciones masivas en el MVP.
- Un pedido no elegible no puede asignarse aunque la UI muestre datos obsoletos.
- El selector solo lista repartidores activos.
- La reasignación invalida inmediatamente al repartidor anterior.
- La UI no implementa reglas de transición; pregunta al servicio.
- Las entregas terminales no admiten reasignación, retiro ni corrección del destino.
- Las confirmaciones y selección de acciones no usan handlers JavaScript inline.

## Criterios de aceptación

- Las columnas aparecen en la lista HPOS sin romper ordenación o paginación.
- Los filtros no exponen pedidos indebidos ni degradan toda la lista.
- El panel carga y guarda en el editor HPOS.
- El panel separa resumen, progreso, prueba, gestión, mapa y actividad sin crear una aplicación administrativa paralela.
- Asignar y reasignar actualiza tabla, meta, evento y nota.
- Retirar asignación elimina el acceso del conductor.
- Un request repetido no genera dos asignaciones activas.
- Los errores muestran notices nativos y conservan el pedido consistente.
- Un POST manipulado no modifica una entrega terminal.
- El timeline muestra etiquetas humanas y no imprime JSON de eventos.
- No hay un menú principal ni un listado LocalPilot duplicado.

## Riesgos

- Hooks distintos entre editores: encapsular adaptadores por pantalla.
- Consultas de filtros HPOS: usar APIs actuales y probar volúmenes razonables.
- Conflicto con plugins de columnas: nombres/IDs únicos y prioridades prudentes.
- Doble clic: idempotencia lógica y bloqueo visual secundario.

## Definición de terminado

El administrador realiza toda la operación del MVP desde Pedidos y el editor del pedido, con controles nativos, servicios compartidos, permisos correctos y paridad funcional bajo HPOS.
