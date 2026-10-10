#!/bin/sh
# Auditoria SOMENTE LEITURA do banco do Desgarrados na VPS (DESG-V2-006.1, Fase A).
# Uso, na raiz da infra-abrasil da VPS:  sh gateway/desgarrados/docs/auditoria-banco-vps.sh > auditoria-desgarrados.txt
# Não altera dados, usuários, volumes nem containers. Não imprime senhas: a senha root
# é lida do ambiente do próprio container e passada ao cliente por MYSQL_PWD.
set -eu

section() { printf '\n===== %s =====\n' "$1"; }
sql() {
    docker compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot --batch --table -e "$1"' sh "$1"
}

section "Data e commit da infra"
date -u +%Y-%m-%dT%H:%M:%SZ
git rev-parse --short HEAD 2>/dev/null || echo "sem git"

section "Containers do banco e do Desgarrados"
docker compose ps mysql desgarrados desgarrados-worker desgarrados-scheduler 2>&1 || true

section "Versão do servidor"
sql "SELECT VERSION() AS versao;"

section "Bancos existentes, charset e tamanho"
sql "SELECT s.SCHEMA_NAME AS banco, s.DEFAULT_CHARACTER_SET_NAME AS charset, s.DEFAULT_COLLATION_NAME AS collation,
       ROUND(COALESCE(SUM(t.DATA_LENGTH + t.INDEX_LENGTH), 0) / 1024 / 1024, 2) AS mb, COUNT(t.TABLE_NAME) AS tabelas
     FROM information_schema.SCHEMATA s LEFT JOIN information_schema.TABLES t ON t.TABLE_SCHEMA = s.SCHEMA_NAME
     GROUP BY s.SCHEMA_NAME, s.DEFAULT_CHARACTER_SET_NAME, s.DEFAULT_COLLATION_NAME ORDER BY s.SCHEMA_NAME;"

section "Usuários (sem credenciais)"
sql "SELECT user, host, account_locked, password_expired FROM mysql.user ORDER BY user, host;"

section "Quem tem privilégio sobre o banco desgarrados"
sql "SELECT user, host, db FROM mysql.db WHERE db IN ('desgarrados', 'desgarrados2') ORDER BY user;"
sql "SHOW GRANTS FOR 'desgarrados_user'@'%';" || echo "usuário desgarrados_user@% não existe"

section "Tabelas do banco desgarrados (linhas aproximadas)"
sql "SELECT TABLE_NAME AS tabela, TABLE_ROWS AS linhas_aprox, ENGINE, TABLE_COLLATION, CREATE_TIME, UPDATE_TIME
     FROM information_schema.TABLES WHERE TABLE_SCHEMA = 'desgarrados' ORDER BY TABLE_NAME;"

section "Contagens exatas das tabelas principais, se existirem"
for table in migrations users sessions jobs failed_jobs cache; do
    exists=$(docker compose exec -T mysql sh -c 'MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot -N -e "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '"'"'desgarrados'"'"' AND TABLE_NAME = '"'"'$1'"'"'"' sh "$table")
    if [ "$exists" = "1" ]; then
        sql "SELECT '$table' AS tabela, COUNT(*) AS linhas FROM desgarrados.\`$table\`;"
    fi
done
sql "SELECT migration, batch FROM desgarrados.migrations ORDER BY id;" 2>/dev/null || echo "sem tabela migrations"

section "Outros serviços que referenciam o banco desgarrados no compose"
grep -n "DB_DATABASE: desgarrados\|desgarrados_user" docker-compose.yml || true

section "Volume do MySQL e backups"
du -sh volumes/mysql 2>/dev/null || echo "volumes/mysql ausente"
ls -la backups 2>/dev/null || echo "diretório backups/ ausente"
(crontab -l 2>/dev/null | grep -Ei "mysqldump|backup|mariadb-dump") || echo "nenhuma rotina de backup no crontab deste usuário"
ls /etc/cron.d 2>/dev/null | grep -Ei "backup|mysql" || echo "nenhum cron de backup em /etc/cron.d"

section "Fim — nenhuma alteração foi feita"
