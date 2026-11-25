#!/bin/bash
# Cambia el bloque «# 2. Rutas del FRONTOFFICE» del .htaccess de la raíz de dualab.es para que
# la web pública la sirva Gestión web (texto para Google y las IA). Enseña el cambio y pide
# confirmación antes de aplicarlo; deja copia del archivo anterior.
# Uso: desde la raíz de este repo →  bash scripts/aplicar-htaccess-seo.sh
# Solo hace falta una vez (o si se vuelve a poner el .htaccess antiguo).
set -e
eval "$(grep -E '^(SSH_KEY|SSH_PORT|SSH_USER|SSH_HOST|REMOTE_HOME)=' deploy.sh)"
SSH_KEY="${SSH_KEY/#\~/$HOME}"
RAIZ=$(dirname "$REMOTE_HOME")
srv() { ssh -p "$SSH_PORT" -i "$SSH_KEY" "$SSH_USER@$SSH_HOST" "$@"; }

echo "→ Comprobando que Gestión web ya está desplegada..."
if ! curl -s https://dualab.es/info/api-backend/public/llms.txt | head -1 | grep -q '^# '; then
  echo "✗ dualab.es/info/api-backend/public/llms.txt no responde todavía: despliega el backend primero (./deploy.sh --back y --ssh)."
  exit 1
fi

comprobar() {
  for u in / /centros /empresas-asociadas /para-alumnado /entidades /familias /contacto /noticias /robots.txt /llms.txt /sitemap.xml /login /gestion-web /noticias/dualab; do
    printf '  %-22s %s\n' "$u" "$(curl -s -o /dev/null -w '%{http_code}' "https://dualab.es$u")"
  done
  echo "  Título de /centros: $(curl -s https://dualab.es/centros | grep -o '<h1[^>]*>[^<]*' | sed 's/<h1[^>]*>//' | head -1)"
}

# Ya aplicado (p. ej. en una ejecución anterior): solo se comprueba
if srv "grep -q '# 2a. Buscadores e IA' $RAIZ/.htaccess"; then
  echo "✓ El .htaccess ya tiene el cambio. Comprobando:"
  comprobar
  exit 0
fi

BLOQUE=$(mktemp)
cat > "$BLOQUE" <<'EOF'
  # 2a. Buscadores e IA: robots.txt, llms.txt y sitemap.xml los genera Gestión web (Laravel de info/)
  RewriteRule ^(robots\.txt|llms\.txt|sitemap\.xml)$ /info/api-backend/public/index.php [L]

  # 2b. Web pública con su texto ya escrito para Google y las IA: la sirve Gestión web
  RewriteRule ^$ /info/api-backend/public/index.php [L]
  RewriteRule ^(centros|contacto|empresas-asociadas|para-alumnado|entidades|noticias)/?$ /info/api-backend/public/index.php [L]
  RewriteRule ^familias(/[a-z0-9-]+)?/?$ /info/api-backend/public/index.php [L]
  RewriteRule ^retos-destacados/[0-9]+(/solicitar-demo)?/?$ /info/api-backend/public/index.php [L]
  # Noticias de la web; /noticias/dualab y /noticias/plataforma son de la app (panel docente)
  RewriteCond %{REQUEST_URI} !^/noticias/(dualab|plataforma)/?$
  RewriteRule ^noticias/[a-z0-9-]+/?$ /info/api-backend/public/index.php [L]

  # 2c. Resto del FRONTOFFICE (panel y fichas sin texto para buscadores): la SPA de /home, como antes
  RewriteRule ^familias(/.*)?$             /home/index.html [L]
  RewriteRule ^retos-destacados(/.*)?$     /home/index.html [L]
  RewriteRule ^proyectos-destacados(/.*)?$ /home/index.html [L]
  RewriteRule ^gestion-web(/.*)?$          /home/index.html [L]
  RewriteCond %{REQUEST_URI} !^/noticias/(dualab|plataforma)/?$
  RewriteRule ^noticias/.+$                /home/index.html [L]
EOF
scp -q -P "$SSH_PORT" -i "$SSH_KEY" "$BLOQUE" "$SSH_USER@$SSH_HOST:$RAIZ/.htaccess.bloque-nuevo"
rm -f "$BLOQUE"

echo "→ Preparando el .htaccess nuevo en el servidor..."
srv bash -s "$RAIZ" <<'REMOTO'
set -e
cd "$1"
grep -q '# 2\. Rutas del FRONTOFFICE' .htaccess || { rm -f .htaccess.bloque-nuevo; echo "✗ No encuentro el bloque «# 2. Rutas del FRONTOFFICE»: no toco nada."; exit 1; }
# Sustituye desde «# 2. Rutas del FRONTOFFICE» hasta la regla ^noticias/.+$ (incluida) por el bloque nuevo
awk -v bloque=.htaccess.bloque-nuevo '
  /# 2\. Rutas del FRONTOFFICE/ { while ((getline l < bloque) > 0) print l; saltar = 1; next }
  saltar && /RewriteRule \^noticias\/\.\+\$/ { saltar = 0; next }
  !saltar { print }
' .htaccess > .htaccess.nuevo
[ "$(grep -c 'info/api-backend/public/index.php' .htaccess.nuevo)" -ge 6 ] || { rm -f .htaccess.nuevo .htaccess.bloque-nuevo; echo "✗ El resultado no es el esperado: no toco nada."; exit 1; }
echo "=== Cambios (− antes / + después) ==="
diff .htaccess .htaccess.nuevo || true
REMOTO

read -p "¿Aplico este cambio? (s/N): " ok
if [[ "$ok" =~ ^[sS]$ ]]; then
  srv "cd $RAIZ && cp .htaccess .htaccess.copia-\$(date +%Y%m%d-%H%M%S) && mv .htaccess.nuevo .htaccess && rm -f .htaccess.bloque-nuevo"
  echo "✓ Aplicado (copia del anterior: .htaccess.copia-…). Comprobando:"
  comprobar
else
  srv "cd $RAIZ && rm -f .htaccess.nuevo .htaccess.bloque-nuevo"
  echo "Cancelado: el .htaccess sigue igual."
fi
