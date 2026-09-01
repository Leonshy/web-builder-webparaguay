# Anexo A — Catálogo de Secciones
### Proyecto 1 — Plataforma de generación web asistida por agentes

**Versión:** 0.1 · **Fecha:** 20 de agosto de 2026 · **Estado:** pasada 3 completa, pendiente de validación contra código

Derivado de los inventarios de `landing` (Enrique Delgado) e `institucional` (IGP Metales).

---

## 0. Cómo leer este documento

Este anexo define **el contrato entre tres subsistemas** que deben coincidir exactamente:

- **El agente de IA** produce un JSON que valida contra estos contratos.
- **El CMS** muestra formularios de edición derivados de estos contratos.
- **El renderer** pinta componentes Blade que consumen estos contratos.

Si los tres no acuerdan la misma forma exacta, el sistema se rompe en los casos que nadie probó.

**Regla de oro:** el contrato de contenido es estable, la variante de layout es intercambiable. Cambiar de variante **nunca** puede perder contenido.

---

## 1. Jerarquía de datos

```
site
├── theme            tokens de marca (colores, tipografía, radios)
├── settings         datos de contacto, redes, integraciones
├── navigation       derivada de las páginas activas
└── pages[]
    ├── slug, title, is_home, is_active, order
    ├── seo          title, description, og_image
    └── sections[]
        ├── envelope común
        └── content   según el tipo
```

**Cambio respecto del legajo v0.4:** el modelo original era `sitio → secciones`. La plantilla institucional es multi-página, así que se agrega el nivel `pages`. La navegación se deriva de las páginas activas y sus anclas: **nunca se escribe a mano**.

Una landing one-page es simplemente un sitio con una sola página.

---

## 2. Tipos de dato

| Tipo | Descripción | Validación |
|---|---|---|
| `texto` | Una línea, sin HTML | Longitud máxima obligatoria |
| `texto_largo` | Varias líneas, sin HTML | Longitud máxima obligatoria |
| `richtext` | HTML restringido | Solo `p, strong, em, ul, ol, li, a, br, h3, h4`. Sanitizado siempre |
| `imagen` | Objeto imagen (§3.2) | — |
| `url` | URL absoluta o relativa | Esquema válido |
| `icono` | Clave del registro (§4) | Debe existir en el registro |
| `boton` | Objeto botón (§3.1) | — |
| `booleano` | true / false | — |
| `numero` | Entero o decimal | — |
| `enum` | Valor de una lista cerrada | Debe estar en la lista |
| `lista<T>` | Colección ordenada de T | Mínimo y máximo obligatorios |

> **Sobre `richtext`:** los inventarios detectaron `{!! !!}` sin sanitizar en múltiples puntos (hallazgo 17 del institucional). En la plataforma **todo `richtext` se sanitiza sin excepción**. El contenido lo genera una IA o lo edita un cliente: ninguno de los dos es una fuente confiable de HTML.

---

## 3. Contratos compartidos

### 3.1 `boton`

Reemplaza las seis implementaciones con nueve campos y nombres inconsistentes detectadas en la landing (hallazgo 9: `label` vs `cta_label`, `url` vs `action_url`). **Un solo contrato, reusado en todos lados.**

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `label` | texto | sí | 30 | — |
| `action` | enum | sí | `url` \| `anchor` \| `whatsapp` \| `email` \| `phone` | `anchor` |
| `url` | url | si `action=url` | — | — |
| `anchor` | texto | si `action=anchor` | 40 | — |
| `target` | enum | no | `_self` \| `_blank` | `_self` |
| `whatsapp_message` | texto_largo | si `action=whatsapp` | 300 | — |
| `email_to` | texto | si `action=email` | 120 | — |
| `email_subject` | texto | no | 120 | — |
| `icon` | icono | no | — | `null` |
| `style` | enum | no | `primary` \| `secondary` \| `ghost` \| `whatsapp` | `primary` |

Los datos de destino de `whatsapp`, `email` y `phone` se toman de `site.settings` cuando no se especifican, para que el agente no tenga que repetirlos en cada botón.

### 3.2 `imagen`

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `src` | texto | sí | — | — |
| `alt` | texto | sí | 125 | — |
| `focal` | enum | no | `center` \| `top` \| `bottom` | `center` |
| `source` | enum | no | `ai` \| `upload` \| `stock` | `ai` |

`alt` es obligatorio. Es accesibilidad y es SEO, y el SEO es la razón por la que el cliente compra.

### 3.3 `item` — ítem genérico de lista

Usado por `feature_list`, `entity_grid` y otros. No todos los campos aplican a todas las variantes.

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `icon` | icono | no | — | `null` |
| `title` | texto | sí | 60 | — |
| `description` | texto_largo | no | 220 | `null` |
| `image` | imagen | no | — | `null` |
| `link` | boton | no | — | `null` |
| `badge` | texto | no | 20 | `null` |

### 3.4 Envelope común de sección

**Todas** las secciones comparten esta cabecera. Resuelve la inconsistencia del hallazgo 11 de la landing, donde `subtitle` y `body` cumplían roles distintos según la sección.

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `id` | texto | sí | — | generado |
| `type` | enum | sí | tipos del §6 | — |
| `variant` | enum | sí | variantes del tipo | primera de la lista |
| `order` | numero | sí | — | — |
| `is_active` | booleano | no | — | `true` |
| `anchor` | texto | no | 40 | derivado del tipo |
| `label` | texto | no | 40 | `null` |
| `title` | texto | no | 80 | `null` |
| `subtitle` | texto_largo | no | 220 | `null` |
| `background` | enum | no | `default` \| `muted` \| `dark` \| `image` | `default` |
| `background_image` | imagen | si `background=image` | — | — |

`label` es el "eyebrow" (el texto chico sobre el título). `title` y `subtitle` son la cabecera. **El cuerpo de la sección va en `content`, nunca en el envelope.**

> `is_active` es `true` por defecto **en todos los tipos, sin excepción**. El inventario detectó que `video` usaba `?? false` mientras las otras ocho usaban `?? true` (hallazgo 5), y que `cambio` usaba `->` en vez de `?->` (hallazgo 4). Un solo comportamiento para todos.

---

## 4. Registro de íconos

Vocabulario **cerrado y único**. Hoy hay dos diccionarios duplicados en la landing (once y trece claves, ocho idénticas) y Bootstrap Icons en el institucional. En la plataforma hay un solo registro, y **esta lista va literal en el prompt del agente**.

| Grupo | Claves |
|---|---|
| Comunicación | `message`, `phone`, `mail`, `whatsapp`, `send` |
| Tiempo y proceso | `calendar`, `clock`, `check`, `refresh`, `flag` |
| Confianza | `shield`, `award`, `star`, `certificate`, `lock` |
| Personas | `users`, `user`, `heart`, `handshake` |
| Negocio | `chart`, `trending`, `target`, `briefcase`, `tag` |
| Industria | `tool`, `gear`, `truck`, `package`, `factory` |
| Naturaleza | `leaf`, `sun`, `drop`, `globe` |
| Ubicación | `map-pin`, `home`, `building` |
| Medios | `image`, `video`, `file`, `download`, `link` |
| Genéricos | `bolt`, `eye`, `activity`, `info`, `plus` |

**Regla:** si el agente devuelve una clave que no está en el registro, el validador la reemplaza por el fallback del tipo de sección. No falla, no muestra un ícono roto, no inventa.

Agregar un ícono es agregar un SVG al registro y una clave a esta lista. Nada más.

---

## 5. Tokens de marca (`site.theme`)

Lo que produce el **agente de Marca**. Se materializa como variables CSS en `:root` vía `ThemeHelper`.

### 5.1 Colores

| Token | Uso | Oblig. |
|---|---|---|
| `primary` | Color de marca, botones, acentos | sí |
| `primary_dark` | Hover, estados activos | derivado |
| `accent` | Segundo color, destacados | no |
| `background` | Fondo general | sí |
| `surface` | Fondo de tarjetas | derivado |
| `text` | Texto principal | sí |
| `text_muted` | Texto secundario | derivado |
| `border` | Bordes y separadores | derivado |
| `dark_bg` | Fondo de secciones oscuras | derivado |
| `dark_text` | Texto sobre fondo oscuro | derivado |

Los `derivado` los calcula el sistema a partir de los obligatorios si el agente no los provee. **El agente solo debe acertar cuatro colores**; el resto es determinístico. Esto reduce el prompt y elimina la posibilidad de una paleta incoherente.

**Validación obligatoria:** contraste mínimo AA entre `text`/`background` y entre `dark_text`/`dark_bg`. Si no cumple, el validador ajusta la luminosidad hasta que cumpla. Un sitio ilegible es peor que un sitio genérico.

### 5.2 Tipografía

| Token | Uso | Default |
|---|---|---|
| `heading_family` | Títulos | de una lista curada |
| `heading_weight` | Peso de títulos | `700` |
| `body_family` | Cuerpo | de una lista curada |
| `body_weight` | Peso de cuerpo | `400` |
| `scale` | enum `compact` \| `normal` \| `spacious` | `normal` |

> **Lista curada, no libre.** El agente elige de un conjunto de entre veinte y treinta combinaciones probadas de Google Fonts, no de las miles disponibles. Elegir tipografías es difícil, la mayoría de las combinaciones son malas, y una tipografía mal elegida arruina un sitio que por lo demás está bien. Se elige de una lista donde todas las opciones funcionan.

### 5.3 Forma

| Token | Valores | Default |
|---|---|---|
| `radius` | `none` \| `sm` \| `md` \| `lg` \| `full` | `md` |
| `shadow` | `none` \| `soft` \| `strong` | `soft` |
| `density` | `compact` \| `normal` \| `airy` | `normal` |

Tres perillas que cambian el carácter de todo el sitio sin tocar una sola sección. Barato en tokens, alto impacto visual.

---

## 6. Catálogo de secciones

### 6.1 `hero`

**Propósito:** primer impacto. Propuesta de valor y llamada a la acción principal.
**Origen:** hero de la landing, hero carousel del institucional.
**Obligatoria:** sí, en la página de inicio. **Repetible:** no.

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `headline` | texto | sí | 70 | — |
| `subheadline` | texto_largo | no | 180 | `null` |
| `body` | texto_largo | no | 300 | `null` |
| `primary_button` | boton | no | — | `null` |
| `secondary_button` | boton | no | — | `null` |
| `image` | imagen | no | — | `null` |
| `background_image` | imagen | no | — | `null` |
| `badge_title` | texto | no | 30 | `null` |
| `badge_subtitle` | texto | no | 40 | `null` |
| `slides` | lista\<slide\> | no | 1-5 | `null` |

`slide` = `{ headline, subheadline, body, image, primary_button }`

**Variantes:**

| Variante | Usa | Ignora |
|---|---|---|
| `split` | `image`, ambos botones, badge | `background_image`, `slides` |
| `fullbg` | `background_image`, ambos botones | `image`, badge, `slides` |
| `minimal` | `headline`, `subheadline`, `primary_button` | imágenes, badge, `slides` |
| `carousel` | `slides` | `headline`, `image`, badge |

> El envelope aporta `label`. En `hero` el título va en `headline`, no en `title` del envelope, porque es un `h1` y tiene reglas de longitud distintas.

---

### 6.2 `page_header`

**Propósito:** encabezado de página interior con navegación de contexto.
**Origen:** las catorce copias de `igp-page-header` del institucional.
**Obligatoria:** sí, en toda página que no sea la de inicio. **Repetible:** no.

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `heading` | texto | sí | 70 | título de la página |
| `description` | texto_largo | no | 180 | `null` |
| `background_image` | imagen | no | — | `null` |
| `icon` | icono | no | — | `null` |
| `show_breadcrumb` | booleano | no | — | `true` |

**El breadcrumb se genera solo** a partir de la jerarquía de páginas. No es contenido editable, y por eso desaparece el problema de tenerlo escrito a mano catorce veces con variaciones.

**Variantes:** `simple` (ignora imagen e ícono) · `image` (usa `background_image`) · `icon` (usa `icon`)

---

### 6.3 `media_text`

**Propósito:** bloque de imagen y texto, con lista opcional de puntos.
**Origen:** enfoque y sobre-mí de la landing; about_teaser y quality_intro del institucional.
**Repetible:** sí.

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `body` | richtext | sí | 1200 | — |
| `image` | imagen | no | — | `null` |
| `items` | lista\<item\> | no | 0-6 | `[]` |
| `button` | boton | no | — | `null` |
| `decoration` | booleano | no | — | `false` |

En `items` se usan solo `title` y `description`; el ícono es fijo por variante (típicamente un check).

**Variantes:** `image_left` · `image_right` · `no_image` (ignora `image` y `decoration`)

---

### 6.4 `feature_list`

**El tipo que más trabajo ahorra.** Absorbe siete implementaciones distintas: proceso en timeline, grilla de áreas, pilares del enfoque, mini-tarjetas del portal técnico, tarjetas de misión/visión/valores, tarjetas de capacitación y la barra de compromiso ambiental.

**Propósito:** lista de elementos con ícono, título y descripción.
**Repetible:** sí.

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `items` | lista\<item\> | sí | 1-12 | — |
| `columns` | enum | no | `2` \| `3` \| `4` | `3` |
| `numbered` | booleano | no | — | `false` |
| `button` | boton | no | — | `null` |

**Variantes:**

| Variante | Layout | Notas |
|---|---|---|
| `grid` | Grilla de tarjetas | Respeta `columns` |
| `timeline` | Línea de tiempo con conectores | Fuerza `numbered=true` |
| `cards` | Tarjetas con elevación e ícono destacado | Respeta `columns` |
| `compact` | Lista vertical con ícono al costado | Ignora `columns` |
| `bar` | Franja horizontal, ideal para un solo ítem | Ignora `columns` y `numbered` |

---

### 6.5 `cta_banner`

**Propósito:** franja de conversión intermedia.
**Origen:** primer-paso de la landing; cta_quote_home y cta_about del institucional.
**Repetible:** sí.

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `body` | texto_largo | no | 240 | `null` |
| `primary_button` | boton | sí | — | — |
| `secondary_button` | boton | no | — | `null` |

**Variantes:** `single` (ignora `secondary_button`) · `dual` · `image` (requiere `background_image` del envelope)

---

### 6.6 `contact_form`

**Propósito:** formulario de contacto con datos de la empresa.
**Origen:** contacto de la landing; contacto y cotización del institucional.
**Repetible:** no.

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `fields` | lista\<enum\> | sí | — | `[name, email, phone, message]` |
| `submit_label` | texto | no | 30 | `Enviar` |
| `success_message` | texto_largo | no | 160 | genérico |
| `box_title` | texto | no | 40 | `null` |
| `box_body` | texto_largo | no | 200 | `null` |
| `show_map` | booleano | no | — | `false` |
| `show_socials` | booleano | no | — | `true` |
| `consent_required` | booleano | no | — | `true` |

Campos disponibles: `name`, `last_name`, `email`, `phone`, `company`, `subject`, `message`, `newsletter`.

Los datos de contacto (dirección, teléfono, email, horario, mapa, redes) vienen de `site.settings`, no de la sección. Esto resuelve el hallazgo 4 del institucional, donde el mismo `map_embed` estaba renderizado en dos lugares sin componente compartido.

**El captcha, la validación, el rate limiting y el envío son del sistema**, no configurables por el agente ni por el cliente.

**Variantes:** `form_only` · `form_info` · `form_map`

---

### 6.7 `stats`

**Propósito:** métricas destacadas con contador animado.
**Origen:** stats de sobre-mí de la landing.
**Repetible:** sí.

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `items` | lista\<stat\> | sí | 2-6 | — |
| `animate` | booleano | no | — | `true` |

`stat` = `{ value: numero (oblig.), suffix: texto máx 5, label: texto máx 40 (oblig.), icon: icono }`

> **Cambio respecto del código actual.** Hoy `value` es texto (`"10+"`) y se parsea con una expresión regular que, si no matchea, cae a cero en silencio. En la plataforma `value` es numérico y `suffix` es un campo aparte. El agente no puede romper el contador.

**Variantes:** `row` · `cards` · `inline`

---

### 6.8 `rich_text`

**Propósito:** contenido libre extenso. Páginas legales, historia institucional, notas.
**Repetible:** sí.

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `body` | richtext | sí | 8000 | — |
| `width` | enum | no | `narrow` \| `normal` \| `wide` | `normal` |

**Variantes:** `default` · `boxed`

---

### 6.9 `gallery`

**Propósito:** muestra visual de instalaciones, trabajos o productos.
**Repetible:** sí.

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `items` | lista\<media_item\> | sí | 3-24 | — |
| `columns` | enum | no | `2` \| `3` \| `4` | `3` |
| `lightbox` | booleano | no | — | `true` |
| `button` | boton | no | — | `null` |

`media_item` = `{ image (oblig.), caption: texto máx 80, video_url: url }`

**Variantes:** `grid` · `masonry` · `strip`

---

### 6.10 `entity_grid`

**Propósito:** grilla de elementos provenientes de una colección administrable. Es la puerta hacia el catálogo.
**Origen:** productos, servicios, certificaciones, posts y documentos destacados del institucional.
**Etapa:** estructura definida en el MVP, **colecciones conectadas en la v1**.
**Repetible:** sí.

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `source` | enum | sí | `products` \| `services` \| `posts` \| `documents` \| `certifications` \| `manual` | `manual` |
| `items` | lista\<item\> | si `source=manual` | 1-12 | — |
| `limit` | numero | no | 1-24 | `6` |
| `columns` | enum | no | `2` \| `3` \| `4` | `3` |
| `filter_category` | texto | no | 60 | `null` |
| `button` | boton | no | — | `null` |

En el MVP solo se usa `source=manual`. Los otros valores quedan definidos para que el esquema no cambie cuando llegue la v1.

**Variantes:** `card_full` (imagen, categoría, título, subtítulo, botón) · `card_compact` (imagen, título, enlace) · `list`

Esto reemplaza las tarjetas de producto, servicio, certificación, post y documento, que hoy tienen entre dos y tres implementaciones cada una (hallazgos 5, 6, 7 y 8 del institucional).

---

### 6.11 `testimonials` *(opcional)*

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `items` | lista\<testimonial\> | sí | 1-12 | — |
| `autoplay` | booleano | no | — | `true` |

`testimonial` = `{ quote: texto_largo máx 400 (oblig.), author: texto máx 60, role: texto máx 60, image: imagen, rating: numero 1-5 }`

**Variantes:** `slider_dark` · `grid` · `single`

---

### 6.12 `faq` *(opcional)*

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `items` | lista\<qa\> | sí | 2-20 | — |
| `layout_columns` | enum | no | `1` \| `2` | `2` |

`qa` = `{ question: texto máx 140 (oblig.), answer: richtext máx 800 (oblig.) }`

> El reparto en dos columnas lo hace el renderer a partir de una sola lista. Hoy el fallback duplica manualmente la lógica en dos arrays separados; en la plataforma **la lista es una sola** y el layout es responsabilidad de la variante.

**Variantes:** `accordion_single` · `accordion_two_col`

---

### 6.13 `pricing_plans` *(opcional)*

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `items` | lista\<plan\> | sí | 1-4 | — |
| `footer_note` | texto_largo | no | 200 | `null` |

`plan` = `{ name (oblig., máx 40), tagline (máx 60), price (texto, máx 20), period (máx 20), description (texto_largo, máx 300), features (lista\<texto\> 0-8), is_featured (booleano), button (boton) }`

**Variantes:** `cards` · `table` · `simple`

---

### 6.14 `video` *(opcional)*

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `video_url` | url | sí | — | — |
| `thumbnail` | imagen | no | — | `null` |
| `caption` | texto | no | 120 | `null` |

Proveedores soportados: YouTube y Vimeo. Si la URL no parsea, la sección no se renderiza y el validador lo reporta.

**Variantes:** `embed` · `thumbnail_modal`

---

## 7. Layout global

No son secciones: son parte del sitio y aparecen en todas las páginas.

### 7.1 `navbar`

| Campo | Tipo | Oblig. | Default |
|---|---|---|---|
| `logo` | imagen | no | texto de marca |
| `logo_dark` | imagen | no | `null` |
| `button` | boton | no | `null` |
| `sticky` | booleano | no | `true` |
| `transparent_on_hero` | booleano | no | `true` |

**Los enlaces se derivan** de las páginas activas y de las anclas de sección de la página actual. Un solo origen, un solo texto por destino. Esto elimina el problema de tener los mismos enlaces escritos tres veces con textos distintos (hallazgo 2 de la landing).

**Variantes:** `simple` · `centered` · `with_topbar`

### 7.2 `footer`

| Campo | Tipo | Oblig. | Límite | Default |
|---|---|---|---|---|
| `logo` | imagen | no | — | texto de marca |
| `description` | texto_largo | no | 240 | `null` |
| `button` | boton | no | — | `null` |
| `copyright` | texto | no | 120 | generado |
| `show_socials` | booleano | no | — | `true` |
| `columns` | lista\<footer_col\> | no | 0-3 | derivadas |

`footer_col` = `{ title (máx 30), links: lista<boton> }`

**Variantes:** `full` · `compact` · `minimal`

---

## 8. Reglas para los agentes

Van literales al prompt de cada agente.

1. **Nunca inventar campos.** Solo los del contrato. Un campo desconocido invalida la salida.
2. **Nunca exceder los límites.** Son restricciones de diseño: pasarse rompe el layout.
3. **Íconos solo del registro** del §4.
4. **Variantes solo de la lista** de cada tipo.
5. **Un valor omitido es válido**; un valor inventado no. Si no hay información, se omite el campo opcional.
6. **Colores: solo los cuatro obligatorios.** El resto lo deriva el sistema.
7. **Tipografía solo de la lista curada.**
8. **No repetir el `title` del envelope dentro del contenido.**
9. **Todo texto en español paraguayo neutro**, salvo instrucción contraria.
10. **`alt` de imagen es obligatorio** y descriptivo, nunca el nombre del archivo.

### Defaults y contenido semilla

> **Regla crítica.** Los fallbacks del código actual contienen copy de negocio real: cuatro pasos de proceso terapéutico, nueve áreas de consulta psicológica, tres testimonios de pacientes, ocho preguntas sobre terapia, y textos institucionales de una metalúrgica.
>
> **Nada de eso puede sobrevivir al refactor.** En la plataforma, un campo sin valor se omite y la sección se adapta o no se renderiza. Un sitio generado que falle en un campo no puede mostrar el contenido del consultorio de otro cliente.

---

## 9. Prueba de aceptación

El catálogo está completo cuando **las dos plantillas actuales se reconstruyen enteras** eligiendo tipos y variantes. Matriz de verificación:

### Landing (Enrique Delgado)

| Sección original | Tipo | Variante | Verificado |
|---|---|---|---|
| hero | `hero` | `split` | ☐ |
| proceso | `feature_list` | `timeline` | ☐ |
| video | `video` | `embed` | ☐ |
| enfoque | `media_text` | `image_left` | ☐ |
| areas | `feature_list` | `grid` | ☐ |
| sobre-mi | `media_text` + `stats` | `image_right` + `row` | ☐ |
| cambio | `testimonials` | `slider_dark` | ☐ |
| planes | `pricing_plans` | `cards` | ☐ |
| faq | `faq` | `accordion_two_col` | ☐ |
| primer-paso | `cta_banner` | `dual` | ☐ |
| contacto | `contact_form` | `form_info` | ☐ |
| legal | `rich_text` | `default` | ☐ |

### Institucional (IGP Metales)

| Sección original | Tipo | Variante | Verificado |
|---|---|---|---|
| hero carousel | `hero` | `carousel` | ☐ |
| barra ambiental | `feature_list` | `bar` | ☐ |
| about teaser | `media_text` | `image_right` | ☐ |
| productos destacados | `entity_grid` | `card_full` | ☐ |
| servicios destacados | `entity_grid` | `card_full` | ☐ |
| certificaciones | `entity_grid` | `card_compact` | ☐ |
| CTA cotización | `cta_banner` | `dual` | ☐ |
| portal técnico | `feature_list` | `cards` | ☐ |
| novedades RSE | `entity_grid` | `card_full` | ☐ |
| galería preview | `gallery` | `grid` | ☐ |
| mapa | `contact_form` | `form_map` | ☐ |
| page header ×14 | `page_header` | `simple` / `image` / `icon` | ☐ |
| about content | `rich_text` + `feature_list` | `default` + `cards` | ☐ |
| calidad | `rich_text` + `entity_grid` | `default` + `card_full` | ☐ |
| capacitación | `feature_list` + `entity_grid` | `cards` + `list` | ☐ |
| contacto | `contact_form` | `form_map` | ☐ |

**Si algo no se puede reconstruir, falta un tipo, una variante o un campo.** Esa es la única señal válida para ampliar el catálogo.

---

## 10. Resumen

| Métrica | Valor |
|---|---|
| Tipos base | 10 |
| Tipos opcionales | 4 |
| Variantes totales | 41 |
| Elementos de layout global | 2 |
| Contratos compartidos | 4 |
| Íconos en el registro | 46 |

Implementaciones originales absorbidas: **26 secciones distintas** entre las dos plantillas, más catorce copias del encabezado de página y entre dos y tres variantes de cada tarjeta.

---

## Pendiente de esta versión

| # | Ítem |
|---|---|
| 1 | Validar contra el código: confirmar que cada campo del contrato existe o es derivable |
| 2 | Definir la lista curada de combinaciones tipográficas |
| 3 | Dibujar y aprobar visualmente las 41 variantes |
| 4 | Definir los SVG del registro de íconos |
| 5 | Escribir el JSON Schema formal a partir de este documento |
| 6 | Decidir qué páginas trae por defecto cada plantilla |

---

*Documento vivo. Versión 0.1 — 20 de agosto de 2026. Anexo del Legajo Técnico P1.*
