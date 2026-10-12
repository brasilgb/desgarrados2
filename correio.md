# DESG-V2-ADMIN-003 — Ativação local do painel administrativo

**Decisão:** autorizada a execução das duas migrations incrementais pendentes no banco local `desgarrados`, condicionada à confirmação do backup íntegro e da conexão correta.

## Etapa 1 — Banco de dados

1. Confirmar que a conexão aponta exclusivamente para o banco local `desgarrados`.
2. Conferir o backup da ADMIN-002 e sua integridade.
3. Executar somente as duas migrations pendentes por meio de `php artisan migrate --force`.
4. Executar `php artisan migrate:status` e verificar a integridade dos dados existentes.
5. Não utilizar `migrate:fresh`, `db:wipe` ou recriação de schema.

## Etapa 2 — Primeiro administrador

1. Confirmar se ainda não existe administrador.
2. Preparar a execução interativa de `php artisan desgarrados:admin:create`.
3. Solicitar ao operador o e-mail e a senha exclusivamente na sessão interativa, sem registrá-los.
4. Não criar usuário fictício nem escolher credenciais pelo executor.
5. Após a criação, definir `DESGARRADOS_ADMIN_BOOTSTRAP_ENABLED=false` e limpar o cache de configuração.
6. Confirmar a impossibilidade de executar novamente o bootstrap.

Caso a criação dependa de dados ou interação do operador não disponíveis, interromper somente essa etapa e registrar as instruções para execução manual.

## Etapa 3 — Painel e permissões

Verificar login, logout, painel administrativo, gerenciamento de usuários, permissões, auditoria e proteção do último administrador.

Preservar a separação entre Administrador e Editor.

## Etapa 4 — Homologação visual

Realizar inspeção desktop e mobile do painel e dos formulários administrativos.

Utilizar o Firefox local quando viável e registrar capturas reais. Verificar teclado, foco, formulários, navegação, mensagens de erro e responsividade.

## Restrições

- Somente ambiente local.
- PHP do host e MariaDB local.
- Sem Docker, push ou deploy.
- Sem conteúdo fictício no banco principal.
- Preservar estrutura e dados existentes.

## Entrega

Registrar tudo em `executed.md`, incluindo migrations executadas, resultado do primeiro acesso, testes, capturas, pendências e instruções operacionais.