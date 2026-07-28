# Roadmap del MVP

## Principio

La versión 1.0 demuestra el flujo operativo completo con la menor superficie propia posible. Todo lo que no sea necesario para asignar, operar y comprobar una entrega queda fuera.

## Hitos

### M0 — Descubrimiento y congelamiento

- inventario real del boilerplate;
- versiones mínimas;
- contratos de estados, hooks y errores;
- decisiones provisionales resueltas.

**Salida:** documentos 01 y 13 aprobados.

### M1 — Fundaciones

- dependencia WooCommerce;
- declaración HPOS;
- esquema y repositorios;
- rol/capacidades;
- servicios de asignación/transición.

**Salida:** flujo programático probado sin UI.

### M2 — Administración nativa

- columnas/filtros;
- panel del pedido;
- asignar/reasignar/retirar;
- perfiles de repartidor;
- settings LocalPilot.

**Salida:** gestor opera desde WooCommerce/Usuarios.

### M3 — Operación del conductor

- endpoint Mis entregas;
- lista/detalle;
- aceptar/iniciar/completar/fallar;
- notices y responsive.

**Salida:** driver completa flujo sin wp-admin.

### M4 — Integraciones

- Mapbox;
- evidencia;
- emails WooCommerce.

**Salida:** destino, prueba y notificaciones integrados.

### M5 — Hardening y release

- seguridad;
- QA;
- traducciones;
- upgrade/uninstall;
- `readme.txt`;
- empaquetado.

**Salida:** candidato 1.0.0.

## Incluido en 1.0

- usuarios repartidores;
- asignación manual;
- estados internos;
- Pedidos/editor nativos;
- Mi cuenta;
- evidencia fotográfica;
- Mapbox con corrección manual;
- emails nativos;
- eventos/notas;
- HPOS.

## Fuera de 1.0

- tablero independiente;
- autoasignación;
- GPS en vivo;
- optimización multi-parada;
- ETA;
- zonas;
- firma o PIN;
- WhatsApp/SMS/push;
- efectivo/liquidaciones;
- entregas parciales;
- múltiples repartidores;
- app nativa;
- tracking público;
- reportes avanzados.

## Después del MVP

### 1.1 — Operación

- dashboard operativo si la evidencia de usuarios lo justifica;
- acciones masivas mejoradas;
- reintentos asíncronos;
- reportes básicos;
- validación puntual de ubicación al completar una entrega, sin tracking continuo.

### 1.2 — Rutas

- grupos de paradas;
- Directions/Matrix;
- ETA básico;
- navegación mejorada.

### 2.0 — Tracking

- consentimiento y privacidad;
- ubicación en vivo;
- portal público;
- notificaciones avanzadas.

## Riesgo de alcance

Cada solicitud nueva debe responder:

1. ¿Es necesaria para completar el escenario de aceptación?
2. ¿WooCommerce ya ofrece una solución nativa?
3. ¿Añade una tabla, pantalla o dependencia nueva?
4. ¿Puede esperar a 1.1 sin bloquear validación?

Si no bloquea el escenario principal, pasa al backlog.

## Definición de terminado del roadmap

Cada hito tiene salida verificable, dependencias claras y una puerta de calidad; no se inicia una fase dependiente hasta aprobar la anterior, aunque tareas independientes puedan prepararse en paralelo.
