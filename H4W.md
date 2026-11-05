# h4w-Anpassungen

Dieser Branch ist `main` **plus** die Anpassungen für das h4w-Forum. Regeln: Dev-Kit
`standards/conventions.md` §12 – Neuerungen zuerst auf `main`, dann `main` → `h4w` mergen,
nie umgekehrt. Deploy ins h4w-Test-/Live-Forum von diesem Branch.

## Anpassungen

| Bereich | Dateien | Zweck |
|---|---|---|
| CSS-Variablen des h4w-Themes | `inc/plugins/plotcp/assets/plotcp.css` | Badge- und Meta-Farben über plugin-eigene `--plotcp-*`-Variablen, die auf `var(--h4w-…, Fallback)` verweisen (8 Stellen). Auf `main` stehen die Fallback-Werte direkt. |
| h4w-Markup (Template-Set des h4w-Themes) | `inc/plugins/plotcp/templates/plotcp_newthread_select.tpl` | Plot-Auswahl im Neuer-Thread-Formular als `div.h4w-post-item` statt `<tr><td>`. Template `plotcp_newthread_select`. |
