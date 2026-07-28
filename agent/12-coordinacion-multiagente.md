# Coordinación multiagente

## Objetivo

Permitir trabajo paralelo sin que los agentes dupliquen lógica, modifiquen simultáneamente el bootstrap o integren contratos incompatibles.

## Roles

### Coordinador

- mantiene tablero, alcance y decisiones;
- asigna ownership;
- resuelve bloqueos;
- no mezcla PRs sin gates.

### Integrador

- único propietario de archivos centrales;
- congela contratos;
- registra hooks/dependencias;
- integra en orden y resuelve conflictos.

### Agentes de frente

- trabajan solo su documento;
- agregan pruebas;
- entregan handoff estructurado;
- no amplían alcance.

### Seguridad y QA

- revisan transversalmente;
- pueden bloquear merge por criterio objetivo;
- no reescriben la implementación sin coordinación.

## Preparación

1. Crear una rama base limpia del boilerplate existente.
2. Inventariar cambios no comprometidos del propietario y preservarlos.
3. Completar `01-arquitectura-boilerplate.md`.
4. Aprobar `13-contratos-compartidos.md`.
5. Crear una rama/worktree por frente.
6. Publicar matriz de ownership.

## Ownership sugerido

| Área | Puede editar | No debe editar |
|---|---|---|
| Datos | `includes/database/`, meta adapter, tests | loader/bootstrap |
| Roles | driver classes, user profile, tests | activador directo |
| Pedidos | integraciones admin, partials, tests | repositorios ajenos |
| Mi cuenta | public/templates, query, tests | reglas de transición |
| Mapbox | maps, settings section, assets mapa | meta directa fuera adapter |
| Emails | email classes/templates | flujo de persistencia |
| Evidencias | proof service/forms/tests | autorización central sin revisión |

## Protocolo de contratos

Un cambio en cualquiera de estos elementos requiere propuesta al integrador:

- claves de meta;
- esquema de tabla;
- estados/transiciones;
- nombres y argumentos de hooks;
- capabilities;
- códigos de error;
- firma pública de servicios;
- namespace/rutas REST.

El integrador actualiza primero `13-contratos-compartidos.md`; luego los agentes consumen el cambio.

Las decisiones transversales se registran en una sección de decisiones del issue/PR coordinador o en un `DECISIONS.md` del repositorio. Cada entrada indica fecha, decisión, motivo, alternativas descartadas y consumidores afectados.

## Formato de tarea para cada agente

```text
Objetivo:
Documento rector:
Archivos permitidos:
Contratos consumidos:
Dependencias disponibles:
Casos de aceptación:
Pruebas requeridas:
Fuera de alcance:
```

## Formato de handoff

```text
Resumen:
Archivos creados/modificados:
Hooks a registrar:
Migraciones/ajustes:
Contratos usados/cambiados:
Pruebas ejecutadas:
Resultados:
Riesgos pendientes:
Pasos manuales:
```

## Secuencia de integración

1. Arquitectura/contratos.
2. Datos y roles.
3. Servicios de asignación/transición.
4. Admin y Mi cuenta.
5. Mapbox.
6. Evidencias.
7. Emails.
8. Seguridad.
9. QA/release.

Integrar commits pequeños por capacidad, no una megafusión final.

## Prevención de conflictos

- Ningún agente edita el loader.
- El integrador aplica los registros de hooks recibidos.
- No usar formateadores sobre archivos ajenos.
- No hacer renombrados globales.
- No modificar archivos generados o vendor.
- Antes del handoff, rebase/merge de la base según política del repo y repetir pruebas.
- Si dos frentes necesitan el mismo archivo de presentación, dividir por partial/clase y dejar el ensamblaje al integrador.

## Manejo de bloqueos

Un agente bloqueado entrega:

- evidencia concreta;
- contrato o dependencia faltante;
- impacto;
- opción mínima recomendada;
- trabajo que puede continuar sin la decisión.

No debe inventar una decisión de producto con impacto transversal.

## Puerta de merge

- alcance del documento cumplido;
- archivos dentro de ownership;
- pruebas y lint aprobados;
- seguridad básica cubierta;
- no cambio silencioso de contratos;
- handoff completo;
- revisión del integrador.

## Definición de terminado

Cada frente tiene propietario, límites y handoff; los contratos se cambian de forma centralizada; el integrador puede ensamblar sin conflictos repetidos y QA recibe un candidato trazable por tarea y criterio.
