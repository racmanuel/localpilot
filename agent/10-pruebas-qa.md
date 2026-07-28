# Pruebas y QA

## Alcance

Validar funcionalidad, seguridad, HPOS, experiencia nativa y regresiones antes de 1.0.0.

## Entornos mínimos

La matriz parte de los metadatos confirmados del plugin:

- WordPress `6.0`;
- WordPress `7.0.2`, declarado como Tested up to;
- última versión estable de WooCommerce compatible con esos entornos;
- versión mínima de WooCommerce pendiente de fijar;
- PHP `7.4`;
- una versión PHP 8.x actual;
- HPOS activado;
- almacenamiento heredado si se declara soporte;
- tema Storefront o equivalente clásico;
- tema de bloques compatible con WooCommerce;
- `WP_DEBUG` y logging activos.

## Estrategia

- Unitarias: transiciones, validadores, normalización.
- Integración: repositorios, esquema, `WC_Order`, roles.
- Funcionales: Admin, Mi cuenta, Mapbox, emails, uploads.
- Seguridad: IDOR, CSRF, capabilities, archivos.
- Compatibilidad: HPOS, multisite sin rotura, temas.

## Matriz crítica

| ID | Caso | Resultado |
|---|---|---|
| HPOS-01 | Asignar con HPOS activo | tabla, meta, evento y nota coherentes |
| HPOS-02 | Consultar sin acceso a posts | mismo resultado |
| ASG-01 | Doble asignación simultánea | una sola activa |
| ASG-02 | Reasignar | anterior pierde acceso |
| ACL-01 | Driver A abre pedido de B | acceso denegado sin fuga |
| ACL-02 | Cliente abre endpoint | pestaña oculta y acceso denegado |
| FSM-01 | Completar desde `assigned` si aceptación/inicio requeridos | rechazado |
| FSM-02 | Repetir completar | idempotente/rechazado seguro |
| MAP-01 | Dirección válida | coordenadas persistidas |
| MAP-02 | API caída | pedido sigue operativo |
| MAP-03 | Corrección manual | no se sobrescribe |
| PRF-01 | JPG válido | attachment relacionado |
| PRF-02 | PHP renombrado JPG | rechazado |
| MAIL-01 | Asignación | un email |
| MAIL-02 | Falta recipient | transición no falla |
| UI-01 | 320 px | acciones utilizables |
| UN-01 | Desinstalar sin opt-in | datos conservados |

## Casos por flujo

### Camino feliz

Crear driver → crear pedido elegible → geocodificar → asignar → recibir email → abrir Mi cuenta → aceptar → iniciar → completar con receptor/evidencia → actualizar pedido → revisar nota/evento.

### Fallo

Asignar → iniciar → fallar con motivo → exigir nota para “Otro” → conservar pedido en estado WooCommerce configurado → notificar admin.

### Reasignación

Asignar a A → A ve entrega → gestor reasigna a B → A pierde acceso → B recibe email y ve entrega → historial conserva ambos.

## Archivos a crear o tocar

- suite de pruebas ya existente;
- fixtures/factories;
- configuración de CI si está autorizada;
- checklist manual de UI;
- no introducir un framework de tests alterno si el repositorio ya tiene uno.

## Tareas

1. Documentar versiones de matriz.
2. Crear fixtures de dos drivers, cliente, gestor y pedidos.
3. Automatizar máquina de estados y permisos.
4. Automatizar esquema/repositorios.
5. Ejecutar flujos HPOS.
6. Simular timeouts/respuestas inválidas de Mapbox.
7. Capturar emails sin enviarlos.
8. Probar uploads límite y maliciosos.
9. Revisar accesibilidad básica.
10. Ejecutar smoke test de activación, upgrade, desactivación y uninstall.

## Criterios de aceptación

- 100% de casos críticos aprobados.
- Cero fatal errors, warnings o notices propios.
- Cero vulnerabilidades altas/críticas conocidas introducidas.
- No regresión visible en Pedidos, Usuarios, Ajustes o Mi cuenta.
- HPOS pasa de extremo a extremo.
- Todos los fallos externos degradan de forma controlada.
- Evidencia de QA adjunta al release.

## Riesgos

- Solo probar camino feliz: matriz obliga negativos.
- Dependencia de Mapbox real: mocks para automatización y un smoke real.
- Tests frágiles por markup de WooCommerce: priorizar comportamiento.
- Diferencias de temas: CSS mínimo y dos smoke tests.

## Severidad y puerta de salida

- **Bloqueante:** fuga o corrupción de datos, acceso cruzado, escalamiento de privilegios, upload peligroso, incompatibilidad HPOS, fatal error o transición inválida.
- **Alta:** emails/asignaciones duplicados, evidencia huérfana recurrente, Mapbox bloqueando el flujo o PII expuesta.
- **Media:** filtros, paginación, traducción o experiencia móvil incorrectos.
- **Baja:** defectos cosméticos sin impacto operativo.

No liberar 1.0.0 con incidencias bloqueantes o altas abiertas. Toda corrección bloqueante/alta debe incorporar una prueba de regresión.

## Definición de terminado

La matriz crítica está aprobada y documentada, las pruebas automatizadas cubren contratos y seguridad, el smoke manual confirma UI nativa y el candidato de release funciona con HPOS sin errores de depuración.
