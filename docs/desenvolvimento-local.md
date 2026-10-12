# Desenvolvimento local

O ambiente de desenvolvimento possui um único ponto de entrada:

```bash
composer dev
```

O comando executa `bin/dev`, inclui `php/conf.d/desgarrados-uploads.ini` no diretório de configuração adicional do PHP e inicia os processos nativos registrados pelo Laravel: servidor HTTP, fila, scheduler, logs e Vite. Assim, o limite editorial de 10 MB funciona sem alteração global no host. `bin/dev` carrega a versão padrão do NVM quando `npm` não está no `PATH`, cenário comum em subprocessos não interativos do Composer. Para comandos diretos como `npm run dev` ou `yarn dev`, inicialize o NVM no terminal (`source "$HOME/.nvm/nvm.sh" && nvm use default`) e confirme `node --version` e `npm --version`.

Para confirmar os limites efetivos e os serviços operacionais, execute `php artisan ops:health`. Arquivos Docker são reservados à implantação futura e não participam deste fluxo.

As imagens públicas usam revalidação obrigatória em todas as requisições (`max-age=0, must-revalidate`). Prévias e originais privados usam `private, no-store`. Essa política prioriza a retirada imediata após bloqueio, ocultação ou revogação de direitos.

## Testes de integração

A suíte de integração usa MySQL ou MariaDB local, mas exige um banco previamente provisionado e separado do banco principal. Configure `TEST_DB_CONNECTION` (`mysql` ou `mariadb`), `TEST_DB_DATABASE` e, se necessário, `TEST_DB_HOST`, `TEST_DB_PORT`, `TEST_DB_USERNAME` e `TEST_DB_PASSWORD`. Credenciais de teste podem usar `DB_USERNAME`/`DB_PASSWORD` como fallback. Não há nome fixo de banco.

Antes de iniciar os testes, a aplicação valida que o endpoint é local, que o banco de testes difere de `DB_DATABASE` e que a conexão efetiva corresponde à configuração. As migrations e limpezas da suíte ocorrem nessa conexão isolada. Exemplo:

```bash
TEST_DB_CONNECTION=mariadb \
TEST_DB_HOST=localhost \
TEST_DB_DATABASE=nome_do_banco_de_testes \
php artisan test
```
