# ADR 001 — Monorepo con fronteras internas estrictas

| | |
|---|---|
| **Estado** | Aceptada |
| **Fecha** | 21 de agosto de 2026 |
| **Decide** | Gerencia General — webparaguay |
| **Afecta** | Estructura del repositorio, despliegue, CI, organización del trabajo |
| **Reemplaza** | — |

---

## Contexto

Surgió la propuesta de separar frontend, backend y demás componentes en repositorios distintos, con el argumento de que eso haría la plataforma más escalable. La preocupación de fondo es legítima: esto es el inicio de un sistema grande, no de un sitio.

Al evaluarla aparecieron dos problemas con el planteo.

**No hay un frontend separado que separar.** La arquitectura es Laravel con Blade renderizado en servidor. Convertirla en API más SPA revertiría una decisión ya tomada y documentada en el legajo, que se apoya en tres razones: el SEO es la razón por la que el cliente PYME compra, el CMS necesita que un cambio se vea al instante sin rebuild, y el equipo es de Laravel.

**Los repos separados resuelven un problema organizacional, no técnico.** Sirven cuando varios equipos necesitan liberar en cadencias distintas sin pisarse. El equipo son dos seniors y un junior.

Pero el instinto de fondo es correcto: **el sistema tiene costuras reales.** El error está en dónde se las ubicó.

---

## Costuras reales del sistema

| Módulo | Qué es | Ciclo de vida | Por qué es una frontera |
|---|---|---|---|
| `schema` | Contrato JSON y validador | Cambia poco | Lo consumen todos los demás |
| `site-runtime` | Renderer + CMS | **Se despliega en N servidores Plesk** | Artefacto distribuido, versión propia |
| `builder` | Cuentas, entrevista, agentes, créditos | Corre solo en el servidor de webparaguay | Nunca sale de casa |
| `provisioning` | Capa sobre WHMCS y Plesk | Cambia con los proveedores | Aislada por diseño (§5.3 del legajo) |
| `modules/*` | Bancard, blog, turnos (v2) | Auditado y versionado por separado | Superficie de seguridad propia |

`site-runtime` es la costura más importante. El legajo ya exige que todas las instancias corran el **mismo paquete versionado del CMS**, desplegado por automatización. Eso obliga a que sea un artefacto instalable con versión propia, independientemente de en qué repositorio viva su código fuente.

---

## Decisión

**Un solo repositorio, con módulos internos de fronteras estrictas verificadas en CI.**

```
webparaguay-builder/
├── packages/
│   ├── schema/          contrato JSON + validador
│   ├── site-runtime/    renderer + CMS → paquete Composer versionado
│   ├── provisioning/    capa WHMCS + Plesk
│   └── modules/         integraciones certificadas (v2)
├── apps/
│   └── builder/         la plataforma: cuentas, entrevista, agentes
├── docs/
└── schema/              contrato publicado (espejo de packages/schema)
```

### Reglas de dependencia

Se verifican en CI. Un pull request que las viole no mergea.

```
schema          → no depende de nada
site-runtime    → depende de schema
provisioning    → depende de schema
modules/*       → dependen de schema y site-runtime
apps/builder    → depende de todos
```

**La regla que más importa:** `site-runtime` **no puede importar nada de `apps/builder`**. La dependencia va en una sola dirección, siempre.

Razón: `site-runtime` corre en el servidor del cliente. Si arrastra código del builder, termina con lógica de facturación, credenciales de orquestación o claves de API en un servidor que no controlás del todo. La frontera es de seguridad antes que de arquitectura.

### Versionado

`site-runtime` lleva versionado semántico propio y se publica como paquete instalable. Su versión es independiente de la del repositorio. El sistema de despliegue registra qué versión corre cada sitio publicado.

---

## Alternativas consideradas

### A. Repositorio por capa (frontend / backend / etc.)

**Descartada.** Parte de una premisa que no aplica: no hay frontend separado. Adoptarla implicaría rehacer la decisión de renderizado en servidor, con costo alto en SEO, en experiencia del CMS y en curva de aprendizaje del equipo.

### B. Repositorio por módulo desde el día uno

**Descartada por ahora.** El costo real no es crear los repos: es que **un cambio que toca el esquema y el renderer se convierte en tres pull requests coordinados en tres repos**, con versionado entre ellos y sin posibilidad de correr un test que los cubra juntos. Con tres personas, ese costo se paga en cada cambio.

Además, hoy no sabemos con certeza dónde están las fronteras definitivas. Fijarlas en repositorios antes de validarlas convierte cada corrección en una migración.

### C. Monorepo sin fronteras internas

**Descartada.** Es el escenario que la preocupación original teme con razón. Sin límites verificados, en seis meses todo depende de todo, `site-runtime` termina importando del builder, y separar deja de ser posible.

---

## Consecuencias

### A favor

- Un cambio que cruza módulos es un solo pull request, con un solo test que lo cubre.
- Refactorizar fronteras es barato mientras todavía estamos aprendiendo dónde van.
- Sin versionado cruzado entre repos ni matrices de compatibilidad.
- `site-runtime` ya sale como artefacto versionado, que es el requisito real.

### En contra

- Requiere disciplina de CI desde el primer día. Sin la verificación automática, las fronteras se erosionan solas.
- El repositorio crece. Irrelevante a esta escala.
- Los permisos son de todo o nada. Relevante recién cuando haya colaboradores externos.

### Asimetría que sostiene la decisión

> **Partir tarde es barato. Partir mal es caro.**

Si mantenemos un repositorio con fronteras limpias y después hace falta separar, es un `git subtree split` y media hora de trabajo, conservando el historial.

Si separamos hoy y las fronteras resultan estar mal puestas, cada corrección es un cambio coordinado entre repositorios: de lo más costoso que existe para un equipo chico.

---

## Cuándo revisar esta decisión

Se separa un módulo a su propio repositorio cuando se cumple **al menos uno**:

1. **Cadencia de liberación distinta.** `site-runtime` necesita liberar en un ritmo que el resto bloquea de forma sostenida.
2. **Equipo dedicado.** Un módulo tiene personas asignadas en exclusiva y la coordinación pesa más que la integración.
3. **Terceros.** Una agencia o desarrollador externo necesita acceso a un módulo sin ver el resto (v3, portal de agencias).
4. **Superficie de seguridad.** Un módulo maneja credenciales de pago con requisitos de auditoría o cumplimiento propios.
5. **Publicación abierta.** El esquema se publica para integradores externos.

**Ninguna se cumple hoy.** El candidato natural a ser el primero en separarse es `site-runtime`, y por eso su frontera es la más estricta desde el inicio.

---

## Verificación

La decisión no vale nada sin la verificación automática. En CI, desde el primer pull request:

1. **Análisis de dependencias entre módulos.** Falla el build ante una importación prohibida, con `deptrac` o equivalente.
2. **Los tests de `site-runtime` corren aislados**, sin el resto del repositorio disponible. Si pasan aislados, la frontera es real.
3. **Validación del esquema** en cada cambio: `python3 schema/validar.py schema/example-site.json`.
4. **Prueba de extracción**, trimestral: verificar que `git subtree split` de `site-runtime` produce un repositorio que compila y testea solo. Es el simulacro que confirma que la salida sigue abierta.

El punto 4 es el que convierte esta decisión en reversible de verdad, en lugar de en una intención.

---

*ADR vivo. Si la realidad contradice esta decisión, se escribe un ADR 00X que la reemplace. No se edita esta.*
