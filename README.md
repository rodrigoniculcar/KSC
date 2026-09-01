# KSC — Portal central

Sitio estático (HTML/CSS, sin build) con dos partes:

- `index.html` — Portal central de KSC. Deja elegir entre:
  - **KSC Consultores** → enlaza a `https://ksconsultores.cl` (sitio ya existente).
  - **KSC Coaching** → enlaza a `coaching/index.html` (sitio nuevo, en construcción).
- `coaching/` — Sitio nuevo de KSC Coaching (formación en coaching), con logo e identidad verde fresca. Hoy es una página "próximamente"; se reemplaza por el sitio definitivo cuando se entregue el contenido/información real.
- `assets/` — CSS compartido (`css/base.css` + estilos por página) e íconos SVG (`img/`).

## Cómo verlo localmente

Al ser HTML/CSS estático, basta con abrir `index.html` en el navegador, o servirlo:

```bash
python3 -m http.server 8000
```

y visitar `http://localhost:8000`.

## Próximos pasos

- Reemplazar el contenido de `coaching/index.html` por el sitio real de KSC Coaching cuando se entregue la información (programas, textos, imágenes, etc.).
- Si KSC Coaching se despliega en su propio dominio, actualizar el enlace de la tarjeta "KSC Coaching" en `index.html` para que apunte a ese dominio en vez de `coaching/index.html`.
- Reemplazar el ícono genérico de `assets/img/logo-consultores.svg` por el logo real de KSC Consultores si se dispone de él.
