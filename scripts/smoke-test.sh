#!/usr/bin/env bash
# Prueba de humo de la API contra una instancia levantada (local o Docker).
# No necesita token de football-data: valida login, permisos por rol, rutas
# protegidas y el formato único de errores.
#
#   ./scripts/smoke-test.sh                 # http://localhost:8000
#   API_URL=http://localhost:8080 ./scripts/smoke-test.sh
set -euo pipefail

API="${API_URL:-http://localhost:8000}"
PASS=0
FAIL=0

check() { # descripción, esperado, obtenido
  if [ "$2" == "$3" ]; then
    PASS=$((PASS + 1)); echo "  ✔ $1"
  else
    FAIL=$((FAIL + 1)); echo "  ✘ $1 (esperado: $2, obtenido: $3)"
  fi
}

# status <método> <ruta> [token] [body] → imprime el código HTTP y deja el cuerpo en /tmp/smoke-body
status() {
  local args=(-s -o /tmp/smoke-body -w '%{http_code}' -X "$1" "$API$2" -H 'Accept: application/json')
  [ -n "${3:-}" ] && args+=(-H "Authorization: Bearer $3")
  [ -n "${4:-}" ] && args+=(-H 'Content-Type: application/json' -d "$4")
  curl "${args[@]}"
}

login() {
  curl -s -X POST "$API/auth/login" -H 'Content-Type: application/json' \
    -d "{\"email\":\"$1\",\"password\":\"Pa$$word123!.\"}" | jq -r '.data.access_token // empty'
}

echo "API: $API"
echo "Autenticación"
check "ruta protegida sin token → 401" 401 "$(status GET /team)"
check "error con formato único (code)" unauthenticated "$(jq -r .error.code /tmp/smoke-body)"
check "login con contraseña incorrecta → 401" 401 "$(status POST /auth/login '' '{"email":"admin@incarail.test","password":"mal"}')"
check "login sin datos → 422" 422 "$(status POST /auth/login '' '{}')"

ADMIN=$(login admin@incarail.test)
VIEWER=$(login consulta@incarail.test)
check "login admin entrega token" true "$([ -n "$ADMIN" ] && echo true || echo false)"
check "login solo consulta entrega token" true "$([ -n "$VIEWER" ] && echo true || echo false)"
check "GET /auth/me → 200" 200 "$(status GET /auth/me "$ADMIN")"
check "reglas CASL en /auth/me" true "$(jq '.data.rules | any(.action == "read" and .subject == "Team")' /tmp/smoke-body)"

echo "Endpoints (base local)"
check "GET /team → 200" 200 "$(status GET /team "$VIEWER")"
check "GET /players → 200" 200 "$(status GET /players "$VIEWER")"
check "GET /team/{id} inexistente → 404" 404 "$(status GET /team/999999999 "$VIEWER")"
check "404 con code not_found" not_found "$(jq -r .error.code /tmp/smoke-body)"
check "GET /players?per_page=500 → 422" 422 "$(status GET '/players?per_page=500' "$VIEWER")"

echo "Permisos por rol (decididos en el servidor)"
check "solo consulta → GET /users → 403" 403 "$(status GET /users "$VIEWER")"
check "admin → GET /users → 200" 200 "$(status GET /users "$ADMIN")"
check "admin → GET /roles → 200" 200 "$(status GET /roles "$ADMIN")"
check "solo consulta no importa ligas nuevas → 403" 403 "$(status GET /competitions/9999 "$VIEWER")"

echo "Sesión"
check "logout → 204" 204 "$(status POST /auth/logout "$VIEWER")"
check "token revocado → 401" 401 "$(status GET /team "$VIEWER")"

echo
echo "Resultado: $PASS correctas, $FAIL fallidas"
[ "$FAIL" -eq 0 ]
