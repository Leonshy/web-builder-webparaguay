# Plataforma de generación web — webparaguay

Plataforma donde una PYME conversa con una IA, ve su sitio web generado y lo
publica en un clic, quedando alojado en infraestructura de webparaguay con un
CMS autoadministrable.

> **Principio rector:** la IA configura, el código lo escriben las personas.
> Un sitio es un JSON validado; el renderer lo pinta con componentes Blade ya
> escritos, testeados y auditados.

## Estructura

```
CLAUDE.md            Contexto y reglas no negociables. Leer primero.
PROMPT-INICIAL.md    Prompts de arranque, uno por sesión.
docs/                Legajo técnico y catálogo de secciones.
schema/              Contrato formal, ejemplo y validador.
```

## Documentos

| Archivo | Qué contiene |
|---|---|
| `docs/legajo-tecnico.md` | Visión, alcance por etapas, arquitectura, negocio, riesgos, KPIs |
| `docs/anexo-a-catalogo-secciones.md` | 14 tipos de sección, contratos de contenido, 41 variantes |
| `schema/site.schema.json` | Contrato formal (JSON Schema 2020-12) |
| `schema/example-site.json` | Sitio de ejemplo que ejercita los 14 tipos |
| `schema/validar.py` | Validador de línea de comandos |
| `docs/adr-001-monorepo-con-fronteras.md` | Estructura del repositorio y reglas de dependencia |

## Validar el esquema

```bash
pip install jsonschema
python3 schema/validar.py schema/example-site.json
```

Sale con código 0 si valida. Usalo en CI.

## Herramientas del entorno

`impeccable` (variantes) · `emil-design-eng` (motion) · `context7` (docs) ·
`playwright-cli` (regresión visual) · `ux-flow-designer` (entrevista guiada) ·
`strix` (pentest por componente)

Guardarraíles en `CLAUDE.md`. El más importante: **ningún color ni tipografía
literal en un componente.** La marca es dato en runtime, no diseño.

Toda herramienta nueva pasa por `skill-security-auditor` antes de instalarse.

## Estado

MVP en definición. Plantillas landing e institucional.
Catálogo en v1, ecommerce y módulos en v2, portal de agencias en v3.

## Pendientes bloqueantes

1. Verificar la API REST de Plesk para despliegue, DNS y SSL
2. Integrar pasarela de pago a WHMCS (prerequisito del MVP)
3. Definir la lista curada de combinaciones tipográficas
4. Dibujar y aprobar las 41 variantes
