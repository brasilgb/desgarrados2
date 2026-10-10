# Operação de produção — Desgarrados 2

Preparação das etapas DESG-V2-006 e DESG-V2-006.1. **Nada aqui foi aplicado à infraestrutura.** O documento descreve o que o operador executa quando a implantação for autorizada.

## 1. Bootstrap do primeiro administrador

Pré-condições:

- migrations aplicadas;
- catálogo de papéis presente (`php artisan db:seed --class=RoleSeeder`, idempotente);
- **conta já cadastrada pelo próprio titular, com e-mail verificado**. Em produção isso exige envio de e-mail configurado (§6).

Nenhum comando cria contas, senhas ou privilégios automaticamente.

1. Identificar o ID da conta escolhida (sem expor dados em logs compartilhados):

    ```bash
    docker compose exec desgarrados php artisan tinker --execute='echo App\Models\User::where("email", "<email-escolhido>")->value("id");'
    ```

2. Conceder o papel Administrador com justificativa. O modo `--bootstrap` só funciona enquanto não houver administrador e só concede Administrador:

    ```bash
    docker compose exec desgarrados php artisan roles:grant <id> administrator --bootstrap --reason="Primeiro administrador designado por <responsável> em <data>"
    ```

3. Se a mesma pessoa também for revisar e publicar, conceder Editor em separado, agora como ator auditado:

    ```bash
    docker compose exec desgarrados php artisan roles:grant <id> editor --actor=<id> --reason="Acúmulo de Editor autorizado por <responsável>"
    ```

4. Conferir em `/administracao/papeis` e na tabela `role_assignment_audits`.

Recusas esperadas: conta inexistente, e-mail não verificado, justificativa ausente, segundo bootstrap e bootstrap de outro papel. A revogação, a exclusão ou a perda de verificação do último administrador verificado é bloqueada.

Matriz:

- Administrador gerencia papéis, mas **não** revisa nem publica sem também ser Editor.
- Editor revisa e publica, mas não gerencia papéis.
- Ninguém aprova a própria revisão.

## 2. Arquitetura e compose

A proposta está em `deploy/compose.desgarrados2.yml`, como **override** do `docker-compose.yml` da infra-abrasil. Ela exige Docker Compose 2.24.4 ou superior por causa de `!override`/`!reset`. O arquivo da infra não precisa ser editado.

| Serviço                 | Origem                    | Comando                             | Health check                                  | Observações                                                                   |
| ----------------------- | ------------------------- | ----------------------------------- | --------------------------------------------- | ----------------------------------------------------------------------------- |
| `desgarrados-mariadb`   | `mariadb:11.8`            | servidor, utf8mb4                   | `healthcheck.sh --connect`                    | Banco `desgarrados2`, usuário `desgarrados2_user`, volume próprio, sem porta. |
| `desgarrados-ssr`       | Dockerfile `--target ssr` | `node docker/ssr-server.mjs`        | `HEALTHCHECK` da imagem (`/health`)           | Um processo, usuário `node`, porta 13714 só na rede interna.                  |
| `desgarrados`           | estágio final             | `php-fpm -F`                        | `php-fpm -t`                                  | `INERTIA_SSR_URL=http://desgarrados-ssr:13714`, timeout 3 s.                  |
| `desgarrados-worker`    | estágio final             | `php artisan queue:work database …` | desativado                                    | O processo encerrado é reiniciado por `restart: unless-stopped`.              |
| `desgarrados-scheduler` | estágio final             | `php artisan schedule:work`         | `php artisan ops:health` (60 s, início 120 s) | `editorial:publish-due` a cada minuto, trava de 10 min.                       |

Decisões:

- **MariaDB dedicado** em vez do MySQL 8.4 compartilhado. As migrations não foram adaptadas a MySQL. O banco MySQL `desgarrados` e o usuário `desgarrados_user` permanecem intactos até a auditoria (§3).
- **Ambiente explícito**: sem `env_file: .env.example` e sem a âncora `x-laravel-environment`, que injetava no Desgarrados o `APP_URL`/banco do VetorOS e a chave do WAHA.
- `depends_on` com `service_started`: uma falha no banco do Desgarrados não impede o Nginx (que depende de `desgarrados`) de subir os outros sites. Se o banco cair, o Desgarrados responde com erro, mas os demais sites continuam.
- Health check do scheduler **sem `--strict`**. Com `--strict`, um aviso de SSR fora (que não afeta o scheduler) ou o primeiro minuto sem heartbeat marcariam o contêiner como unhealthy. O modo normal falha só com banco fora, publicador parado há mais de 5 min ou agendamento vencido. No Compose puro, "unhealthy" sinaliza mas não reinicia; o reinício ocorre quando o processo termina.
- O renderer expõe `/render`, `/health` e `/shutdown` **sem autenticação**: nunca publicar a porta 13714 nem roteá-la pelo Nginx.

O `docker-entrypoint.sh` executa `php artisan optimize` (cache de config, rotas, views e eventos) como `www-data` quando `APP_ENV=production`, a partir do ambiente do contêiner. Uma falha ali interrompe o contêiner de forma visível.

Validação sem daemon (só lê o compose; nada é iniciado):

```bash
docker compose -f docker-compose.yml -f gateway/desgarrados/deploy/compose.desgarrados2.yml config --quiet
```

## 3. Banco de produção

Antes de qualquer migração, rodar a auditoria **somente leitura** na VPS, na raiz da infra-abrasil:

```bash
sh gateway/desgarrados/docs/auditoria-banco-vps.sh > auditoria-desgarrados.txt
```

O script lista bancos, usuários (sem credenciais), privilégios sobre `desgarrados`, tabelas e contagens, o volume do MySQL e a existência de backups. Ele não altera nada.

Decisão conforme o resultado:

- `desgarrados` (MySQL) vazio ou só com tabelas de framework, sem usuários reais: manter intocado (não reutilizar), seguir com o MariaDB `desgarrados2` e, após a estabilização, decidir em etapa própria sobre arquivar ou remover.
- Qualquer conteúdo real: **não** reutilizar nem migrar destrutivamente. Fazer dump (`mysqldump --single-transaction desgarrados`), guardar fora do servidor e definir em etapa própria se algo deve ser importado.

Novas variáveis no `.env` da infra (valores só no servidor):

| Variável                                                                                                                | Uso                                                                  |
| ----------------------------------------------------------------------------------------------------------------------- | -------------------------------------------------------------------- |
| `DESGARRADOS_DB_ROOT_PASSWORD`                                                                                          | Root do MariaDB do Desgarrados. Vazia, o MariaDB recusa inicializar. |
| `DESGARRADOS_DB_PASSWORD` (existente)                                                                                   | Passa a ser a senha de `desgarrados2_user` no MariaDB.               |
| `DESGARRADOS_MAIL_*` (`MAILER`, `HOST`, `PORT`, `USERNAME`, `PASSWORD`, `SCHEME`, `FROM_ADDRESS`)                       | Envio real de e-mail (verificação e redefinição de senha).           |
| Opcionais: `DESGARRADOS_APP_ENV`, `DESGARRADOS_APP_DEBUG`, `DESGARRADOS_LOG_LEVEL`, `DESGARRADOS_SESSION_SECURE_COOKIE` | Padrões de produção; sobrescrever só para teste local em HTTP.       |

Backup do MariaDB (não existe política hoje; definir antes de abrir ao público):

```bash
docker compose exec -T desgarrados-mariadb sh -c 'mariadb-dump -uroot -p"$MARIADB_ROOT_PASSWORD" --single-transaction --routines desgarrados2' | gzip > backups/desgarrados2-$(date +%Y%m%d%H%M).sql.gz
```

## 4. Comportamento em falha do SSR

Observado em homologação local, inclusive com a configuração de produção (cache de config, `APP_DEBUG=false`, log em stderr):

| Situação                  | Resultado                                                                                                                                      |
| ------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| Renderer ativo            | HTML inicial com h1, título, descrição, canonical, Open Graph e parágrafos.                                                                    |
| Renderer desligado        | HTTP 200 em ~50–60 ms; HTML sem pré-renderização, com `<script data-page>`; o navegador renderiza. Aviso `SSR indisponível` no log, sem props. |
| Renderer travado          | HTTP 200 após o timeout de 3 s (`INERTIA_SSR_TIMEOUT`), mesmo fallback.                                                                        |
| Reinício do renderer      | SSR volta na requisição seguinte, sem reiniciar o PHP.                                                                                         |
| `SIGTERM` (`docker stop`) | Encerramento limpo em ~13–16 ms, código 0.                                                                                                     |

Páginas administrativas continuam exigindo sessão, com `X-Robots-Tag: noindex, nofollow` e `Cache-Control: no-store, private`, com ou sem SSR.

## 5. Diagnóstico

```bash
docker compose exec desgarrados php artisan ops:health          # banco, SSR, scheduler, agendamentos vencidos, falhas, configuração
docker compose exec desgarrados php artisan ops:health --json   # para automação
docker compose exec desgarrados php artisan ops:health --strict # avisos também falham
docker compose exec desgarrados php artisan inertia:check-ssr
docker compose exec desgarrados php artisan schedule:list
docker compose ps desgarrados-mariadb desgarrados-ssr desgarrados desgarrados-worker desgarrados-scheduler
docker compose logs --tail=100 desgarrados desgarrados-ssr desgarrados-scheduler desgarrados-mariadb
```

`ops:health` não imprime valores de configuração, credenciais nem caminhos. SSR indisponível é aviso, porque a aplicação continua servindo. Falhas de publicação aparecem como `schedule_failed` e bloqueios como `schedule_blocked` em `audit_entries`.

## 6. Configuração de produção

Já fixada no override (§2):

- `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://desgarrados.com.br`;
- `SESSION_SECURE_COOKIE=true`, `SESSION_HTTP_ONLY=true`, `SESSION_SAME_SITE=lax`;
- `LOG_CHANNEL=stderr` (vai para `docker compose logs`, com a rotação existente), `LOG_LEVEL=warning`;
- sessão, cache e fila em banco;
- SSR interno.

Validado localmente com essa configuração:

- cookies `secure; httponly; samesite=lax`;
- páginas 404/419 sem stack trace nem caminhos;
- `ops:health --strict` aprovado com heartbeat presente;
- `optimize` concluído.

**Pendente:** e-mail. Sem `DESGARRADOS_MAIL_*`, o mailer é `log` e nenhum e-mail de verificação sai, o que impede o bootstrap (§1).

## 7. Plano de implantação

Executar só após autorização explícita, com Docker Compose 2.24.4 ou superior, na raiz da infra-abrasil:

1. **Auditoria e backup:** rodar `docs/auditoria-banco-vps.sh` e decidir sobre o banco MySQL `desgarrados` (§3). Fazer dump do MySQL inteiro antes de qualquer mudança: `mysqldump --all-databases --single-transaction`.
2. **Variáveis:** acrescentar ao `.env` da infra as variáveis do §3. Conferir com `docker compose -f docker-compose.yml -f gateway/desgarrados/deploy/compose.desgarrados2.yml config --quiet`.
3. **Código:** atualizar `gateway/desgarrados` para o commit aprovado.
4. **Build:**

    ```bash
    docker compose -f docker-compose.yml -f gateway/desgarrados/deploy/compose.desgarrados2.yml build desgarrados desgarrados-ssr desgarrados-worker desgarrados-scheduler
    ```

5. **Banco e renderer** (sem tocar nos demais serviços):

    ```bash
    docker compose -f docker-compose.yml -f gateway/desgarrados/deploy/compose.desgarrados2.yml up -d --no-deps desgarrados-mariadb desgarrados-ssr
    ```

    Aguardar `healthy` em `docker compose ps`.

6. **Migrations** (somente incrementais; nunca `migrate:fresh`). Num contêiner avulso, para que o PHP antigo siga servindo:

    ```bash
    docker compose -f docker-compose.yml -f gateway/desgarrados/deploy/compose.desgarrados2.yml run --rm --no-deps desgarrados php artisan migrate --force
    ```

    Depois rodar `db:seed --class=RoleSeeder --force` (catálogo de papéis, idempotente) e `territory:import-ibge` conforme o procedimento da DESG-V2-004.

7. **Aplicação:**

    ```bash
    docker compose -f docker-compose.yml -f gateway/desgarrados/deploy/compose.desgarrados2.yml up -d --no-deps desgarrados desgarrados-worker desgarrados-scheduler
    docker compose exec -T nginx nginx -s reload
    ```

8. **Homologação:**
    - `curl -I https://desgarrados.com.br/up`;
    - `ops:health`;
    - HTML inicial de `/` com h1;
    - `/administracao` redireciona para login;
    - cookies com `secure`;
    - parar `desgarrados-ssr` e confirmar o fallback (200 sem h1);
    - religar o renderer.
9. **Bootstrap administrativo** pelo operador (§1), após o e-mail funcionar.

## 8. Rollback

Estado de referência registrado antes da preparação: infra-abrasil em `b7c7782`, com `docker-compose.yml`, `.env.example`, `mysql-init/` e `nginx/` sem alterações locais.

- **Antes das migrations (passos 1–5):**
    - `docker compose stop desgarrados-mariadb desgarrados-ssr`;
    - subir de novo os serviços do Desgarrados **sem** o `-f` do override: `docker compose up -d --no-deps desgarrados desgarrados-worker desgarrados-scheduler`.

    O MySQL `desgarrados` nunca foi alterado.

- **Depois das migrations:** o mesmo procedimento. Os dados do MariaDB ficam em `volumes/desgarrados-mariadb` e não são apagados. Para descartar uma tentativa, primeiro fazer o dump (§3); só depois, com decisão explícita, remover o volume.
- **Código:** voltar `gateway/desgarrados` ao commit anterior e reconstruir os serviços do Desgarrados.
- Nenhum passo do rollback toca o MySQL compartilhado, o Nginx (além do `reload`) ou outros projetos.
