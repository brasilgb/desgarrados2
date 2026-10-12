# DESG-V2-ADMIN-003 — Ativação local do painel administrativo

Data: 2026-10-10, America/Sao_Paulo.

Status: **`correio.md` mudou após ADMIN-002; hash atual `775f080bb34b9e039ba142f0f9bf25aa6f5298686ef4675e3a4edf557e996f95` (anterior: `90ee8fa077c238bacf5402f18b6be4942fefe00c59de2acf732aed84c3e94ea4`). As migrations locais autorizadas foram aplicadas. Bootstrap real do administrador ficou preparado, mas aguarda o operador inserir seus dados confidencialmente no terminal.**

## Banco e migrations

- Confirmei o alvo `desgarrados` em MariaDB local 11.8.6. O dump da ADMIN-002 passou novamente na verificação SHA-256 (`7cc9c850cb6968a63e21418b52459f3a1659b60a06712fb263c909c7a9e79add`) e já havia sido restaurado e comparado em schema isolado antes desta janela.
- Antes da alteração, `migrate:status` mostrava apenas estas duas pendentes: `2026_10_10_150000_add_revision_rights_metadata` e `2026_10_10_160000_add_administration_and_rights_evidence`. `php artisan migrate --force`, com conexão explicitamente apontada a `desgarrados`, aplicou ambas com sucesso. Status final: dez migrations aplicadas, nenhuma pendente. Não usei `migrate:fresh`, `db:wipe` nem recriei schema.
- O catálogo de papéis estava ausente. Executei o `RoleSeeder` idempotente documentado para preparar o bootstrap; verificação posterior encontrou cinco papéis, incluindo Administrador.
- Integridade pós-migration: `users=0`, `publications=0`, `revision_rights_evidence=0`, `administration_audits=0`. Não houve criação de conta ou inserção de conteúdo cultural. Nenhum outro banco foi selecionado para migrations.

## Primeiro administrador e gestão

- A consulta confirmou zero administradores ativos/verificados. O comando foi verificado com a opção de bootstrap desabilitada e recusou antes de solicitar dados. A suíte isolada cobre o caminho interativo, hash, ausência de senha em saída, unicidade e proteção contra segundo bootstrap.
- Não criei a conta: o nome, o e-mail sob controle do titular, a senha e a justificativa precisam ser fornecidos pelo operador durante uma sessão terminal interativa; esses dados não foram solicitados nem registrados nesta conversa. O catálogo agora existe, então a etapa operacional restante é executável.
- O `.env` local ainda contém `DESGARRADOS_ADMIN_BOOTSTRAP_ENABLED=true`. Antes do uso, confirmar o titular e a configuração de e-mail; executar `php artisan desgarrados:admin:create` localmente, responder aos prompts e confirmar a designação. Logo após sucesso, definir a variável como `false` e executar `php artisan config:clear` (ou reconstruir o cache de configuração conforme o ambiente). Não compartilhar senha, saída com dados pessoais ou arquivo `.env`.
- Não foi possível concluir login/logout com a conta real antes de sua criação. Os fluxos e permissões permanecem cobertos por testes.

## Homologação e pendências

- `composer test`: **181 testes, 175 aprovados, 6 ignorados; 1.237 assertions**. Pint passou; PHPStan passou com zero erros.
- NVM: Node 24.16.0/npm 11.18.0. `npm run check:fix`, `npm run check`, `npm run types:check` e `npm run build:ssr` passaram. Formatação: 104 arquivos; lint: 91 sem avisos/erros; cliente SSR: 2.327 módulos e bundle SSR: 122 módulos. Aviso opcional de `fontaine`, sem falha.
- Firefox está instalado, mas Playwright/axe-core e geckodriver não estão disponíveis. Não gerei capturas reais nem declarei validação visual desktop/mobile, teclado ou auditoria axe; continuam pendentes após o operador poder acessar o painel.
- `git diff --check`: passou. Sem Docker, push ou deploy.

---

# DESG-V2-ADMIN-002 — Homologação e ativação administrativa local

Data: 2026-10-10, America/Sao_Paulo.

Status: **nova solicitação em relação ao registro ADMIN-001/UX-009. Homologação local concluída; ativação real do administrador e aplicação de migrations permanecem condicionadas a decisão/autorização do operador.** Hash de `correio.md`: `90ee8fa077c238bacf5402f18b6be4942fefe00c59de2acf732aed84c3e94ea4` (anterior registrado: `43d61c4b340387ea4842b3fba36fe9a7c2aa5f7968d43d271078b89698d91541`).

## Validação e regressão corrigida

- `composer test`: **181 testes, 175 aprovados, 6 ignorados; 1.237 assertions**. Pint passou e PHPStan passou com zero erros. A primeira execução identificou uma expectativa incorreta no teste de upload recusado; ajustei-o para confirmar que o comprovante válido anterior permanece `current`, e a repetição completa passou.
- Inicializei Node pelo NVM local (Node 24.16.0, npm 11.18.0). `npm run check:fix` e `npm run check` passaram (104 arquivos formatados; 91 sem avisos/erros de lint), `npm run types:check` passou e `npm run build:ssr` passou (2.327 módulos). O build imprimiu apenas o aviso opcional de `fontaine`.
- As rotas de primeiro administrador, gestão, permissões e proteção do último administrador foram cobertas pela suíte no schema isolado. O comando `desgarrados:admin:create` também foi chamado com bootstrap explicitamente desabilitado e recusou a execução antes de solicitar dados. Não criei conta fictícia nem defini credenciais.
- `git diff --check`: passou.

## Banco, backup e plano incremental

- Conexão local somente leitura confirmou MariaDB 11.8.6, schema de origem `desgarrados`, com 29 tabelas. `php artisan migrate:status` encontrou oito migrations aplicadas e duas pendentes: `2026_10_10_150000_add_revision_rights_metadata` e `2026_10_10_160000_add_administration_and_rights_evidence`.
- Backup consistente `--single-transaction --routines --triggers` gerado em `/tmp/desgarrados-admin002.HBB2zm/desgarrados.sql`, modo privado, SHA-256 `7cc9c850cb6968a63e21418b52459f3a1659b60a06712fb263c909c7a9e79add`. Restauração isolada concluída no schema novo `desgarrados_admin002_restore`: 29 tabelas; contagens de migrations (8), usuários (0) e publicações (0) conferem com a origem.
- `php artisan migrate --pretend` foi executado somente contra a cópia restaurada; listou as duas migrations pendentes sem executar DDL. O ensaio confirma o plano incremental no MariaDB local, não substitui a confirmação da versão/engine do servidor de destino nem uma revisão operacional do dump.
- A suíte criou e utilizou o schema descartável separado `desgarrados_admin002_test`. Os dois schemas de ensaio e o dump em `/tmp` foram mantidos para inspeção; não foram removidos.
- **Nenhuma migration foi aplicada a `desgarrados`.** Não executei `migrate:fresh`, `db:wipe`, Docker, push ou deploy.

## Primeiro administrador e pendências de operação

- O `.env` local contém `DESGARRADOS_ADMIN_BOOTSTRAP_ENABLED=true`. Não alterei esse arquivo local, não rodei o comando contra `desgarrados` e não criei administrador: falta autorização e identificação do titular operacional. Antes de uso real, o operador deve confirmar o titular/e-mail, janela autorizada e e-mail de verificação configurado; após a criação, desabilitar bootstrap e limpar/cachear a configuração conforme o procedimento em `docs/operacao-producao.md`.
- A verificação de login/logout, navegação administrativa e acesso negado está coberta por testes automatizados; não foi feita sessão visual com conta real, pois nenhuma conta operacional foi provisionada.
- Firefox está instalado, mas Playwright/axe-core e geckodriver não estão disponíveis. Portanto, não foram produzidas capturas nem concluídos teste manual de teclado e homologação visual desktop/mobile. A adequação visual e a auditoria de acessibilidade continuam pendentes.
- A base principal mostra zero usuários e zero publicações. Sua preservação foi mantida; qualquer decisão para aplicar as duas migrations exige autorização explícita posterior.

---

# DESG-V2-ADMIN-001 — Primeiro administrador e painel de gestão

Data: 2026-10-10, America/Sao_Paulo.

Status: **`correio.md` difere do registro UX-008 e contém duas solicitações novas (ADMIN-001 e UX-009); ambas foram implementadas.** Hash atual: `43d61c4b340387ea4842b3fba36fe9a7c2aa5f7968d43d271078b89698d91541`; hash anterior registrado em UX-008: `b668a3890481a7cbb51ef450e7560541accad08ab4bfd8c92679bb67569425f`.

## Administrador e gestão

- Criado `php artisan desgarrados:admin:create`, interativo e desabilitado por padrão. Exige ativação temporária explícita por `DESGARRADOS_ADMIN_BOOTSTRAP_ENABLED=true`, conexão MySQL/MariaDB, confirmação de titularidade do e-mail e designação; valida unicidade e senha forte, usa hash Laravel, cria conta verificada/ativa e atribui apenas o papel existente de Administrador. Uma segunda inicialização é impedida. A senha não é impressa nem registrada.
- A área administrativa ganhou listagem/pesquisa, cadastro, edição, ativação/desativação e trilha de auditoria. Novos cadastros não recebem papel automaticamente. A última conta administradora ativa/verificada não pode ser desativada, perder verificação ou perder o papel necessário. A conta desativada é retirada da sessão e bloqueada.
- O painel agora aponta para operações realmente disponíveis (acervo/editorial, regiões, curadoria, usuários e auditoria), sem métricas ou botões de configuração fictícios. Os controles editoriais e a separação entre papéis foram preservados; Administrador não recebeu bypass editorial.
- O procedimento de primeiro acesso está documentado em `docs/operacao-producao.md`. O comando foi exercitado em testes isolados, não executado contra a base principal.

## UX-009 — Direitos, áudio e prontidão operacional

- Comprovantes são vinculados à revisão editorial e guardados no disco privado `rights`, fora do acesso público. Upload limita tamanho e tipos PDF/JPEG/PNG, registra MIME e SHA-256, autor e auditoria; versões anteriores são preservadas como supersedidas. Download é autorizado e usa cabeçalhos privados/sem cache. A revogação exige justificativa, registra auditoria, oculta publicação vigente e cancela agendamento quando aplicável.
- A proposta de áudio está em `docs/proposta-audio-cultural.md`: separa direitos da composição e do fonograma, define requisitos de player acessível, créditos e transcrição. Reprodução pública não foi implementada.
- Removida a restrição artificial de sufixo `_test` de nomes de banco nas migrations editoriais; proteção contra testes destrutivos permanece na conexão de teste. Documentação operacional cobre backup, restauração isolada, prévia de migrations e plano de implantação. Homologação executada em MariaDB 11.8.6 efêmera; MySQL não foi verificado.
- Suíte PHP completa aprovada na execução registrada: **180 testes, 174 aprovados, 6 ignorados; 1.220 assertions**, incluindo Pint e PHPStan sem erros. Testes focados de administração/direitos passaram (7 testes, 73 assertions) antes dos últimos acréscimos de caso para segunda inicialização e cancelamento de publicação agendada; esses acréscimos não tiveram nova execução completa registrada nesta retomada.
- Os scripts de concorrência editorial e de mídia passaram integralmente. O build SSR passou (2.327 módulos cliente + SSR) antes do último ajuste visual pequeno; lint e checagem de tipos também passaram antes desse ajuste. Nesta retomada, `git diff --check` passou, mas não foi possível repetir Node: `npm`/`node` não estão disponíveis no `PATH` desta sessão.
- Accessibility: axe/Playwright e geckodriver não estão disponíveis no ambiente, então navegação por teclado automatizada, auditoria axe e homologação visual desktop/mobile não foram concluídas nesta rodada. Esse critério permanece pendente; não se declara conformidade completa de UX-009.

## Segurança operacional e arquivos

- Não houve execução de migration no banco principal, Docker, push ou deploy; nenhum conteúdo cultural fictício foi inserido nele. As migrations/testes usaram apenas banco MariaDB isolado e descartável.
- Principais mudanças: `app/Console/Commands/CreateFirstAdministrator.php`, `app/Http/Controllers/Admin/AdministrationController.php`, `app/Actions/Administration/`, `app/Actions/Editorial/ManageRightsEvidence.php`, `app/Actions/Editorial/RevokePublicationRights.php`, `app/Http/Controllers/Editorial/RightsEvidenceController.php`, `app/Models/RevisionRightsEvidence.php`, `database/migrations/2026_10_10_160000_add_administration_and_rights_evidence.php`, `resources/js/pages/admin/users.tsx`, telas editoriais, `docs/operacao-producao.md` e `docs/proposta-audio-cultural.md`.
- Testes adicionados em `tests/Feature/AdministrationBootstrapTest.php` e `tests/Feature/RightsEvidenceTest.php`.

---

# DESG-V2-UX-008 — Preparação editorial para operação real

Data: 2026-10-10, America/Sao_Paulo.

Status: **instrução diferente da UX-007; implementada e homologada localmente em banco MariaDB descartável.** `correio.md` mudou: SHA-256 atual `b668a3890481a7cbb51ef450e7560541accad08ab4bfd8c92679bb67569425f5` (anterior UX-007: `cb4c5f271ab08034f7ef254bd5bd31c9e0883551902898741205c2241f9668d3`).

## Direitos autorais por revisão

- Adicionei à revisão os campos versionados de situação autoral (`original`, `authorized`, `public_domain`, `licensed`, `unverified`), autor, titular, fundamento, referência da autorização/licença/obra e restrições. A migração `2026_10_10_150000_add_revision_rights_metadata.php` só aceita conexão MySQL/MariaDB com banco cujo nome termine em `_test`.
- A criação/edição valida esses dados e cópia para nova versão preserva a informação anterior, salvo alteração explícita. A publicação imediata e o agendamento revalidam: original requer autor; uso autorizado/licenciado exige titular, fundamento e referência; domínio público exige fundamento e referência; situação não comprovada bloqueia publicação.
- O formulário reúne esses campos num painel de direitos e mostra a situação no resumo editorial. Fontes continuam separadas: atribuição não é tratada como prova de autorização.

## Prévia e validação operacional

- A tela administrativa agora oferece “Pré-visualizar como visitante”. A rota exige autenticação e autorização sobre a publicação, confere que a revisão pertence à publicação e apresenta a revisão candidata no mesmo componente público, marcada como privada e `noindex`. Teste cobre visitante sem sessão, usuário sem acesso, autor autorizado e confirmação de que o slug público ainda retorna 404 antes da publicação.
- Testes novos confirmam que revisão sem situação autoral verificável não publica e que dados completos de autorização são persistidos. A suíte completa: **173 testes, 167 aprovados, 6 ignorados; 1.147 assertions**.
- `composer test`: Pint e PHPStan passaram (zero erros). Concorrência editorial e de mídia passou integralmente: versionamento/escritores, slug, troca de publicação/destaques, deduplicação e processamento de mídia, capa única e conflito de associação.
- `npm run check`: 102 arquivos formatados e 90 sem erros/avisos de lint. `npm run types:check`: passou. `npm run build:ssr`: cliente (2.323 módulos) e SSR aprovados; aviso opcional de `fontaine` não bloqueante.
- Não há axe/Playwright instalado no projeto nem navegador de automação disponível neste ambiente; por isso não executei auditoria automatizada de acessibilidade, navegação por teclado real ou captura visual desta rodada. O HTML usa controles nativos rotulados e mensagens com `role="alert"`; isso é revisão de código, não substitui teste assistivo. Leitor de tela também não foi testado.
- Não foram geradas novas capturas. Os testes HTTP da prévia e o bloqueio autoral são evidências funcionais.

## Limites e segurança

- Migrations e suíte rodaram exclusivamente em `desgarrados_ux008_test`, em MariaDB 11.8.6 efêmera local. Nenhuma migration, seed ou conteúdo foi aplicado ao banco principal; não houve Docker, push, deploy ou dependência nova.
- A base e o servidor temporários desta rodada foram encerrados/removidos ao final.
- A referência de autorização/licença é um campo de registro, não armazenamento nem verificação criptográfica do documento. A operação real ainda precisa definir repositório e retenção dos comprovantes, além de orientar revisores para conferência material antes da aprovação.
- `git diff --check`: aprovado.

## Arquivos desta rodada

- `app/Enums/TextRightsStatus.php`, `app/Models/PublicationRevision.php`, `app/Actions/Editorial/EditorialWorkflow.php`.
- `database/migrations/2026_10_10_150000_add_revision_rights_metadata.php`.
- `app/Http/Controllers/Editorial/PublicationController.php`, `routes/editorial.php`.
- `resources/js/components/editorial-fields.tsx`, `resources/js/pages/editorial/create.tsx`, `resources/js/pages/editorial/show.tsx`, `resources/js/pages/editorial/publication.tsx`, `resources/js/types/editorial.ts`.
- Testes: `tests/Feature/EditorialTest.php`, `tests/Feature/EditorialHomologationTest.php`, `tests/Feature/RegionalCurationTest.php`, `tests/Feature/OperationsTest.php`, `tests/Feature/MediaTest.php`, `tests/Support/editorial-concurrency.php`.
- Relatório: este arquivo.

---

# DESG-V2-UX-007 — Fluxo editorial e descoberta pública

Data: 2026-10-10, America/Sao_Paulo.

Status: **solicitação diferente da rodada UX-006; homologação executada localmente em base MariaDB isolada.** `correio.md` mudou: SHA-256 atual `cb4c5f271ab08034f7ef254bd5bd31c9e0883551902898741205c2241f9668d3` (anterior UX-006: `1f3e6c3f7653b5cf1f09832b0cd0ab57cbb409e250fde597f09b44dcb500bf1d`).

## Auditoria e fluxo DEMO

- A revisão abrangeu autorização e matriz de papéis, ciclo editorial e revisões, taxonomias, municípios/regiões, direitos de mídia, programação em destaque e descoberta em detalhe, acervo, página regional e home. As rotas mantêm suas policies/gates; não foram ampliadas permissões.
- Acrescentei um teste de integração HTTP completo em `EditorialHomologationTest.php`: cria conteúdo original explicitamente fictício, categoria, tag, município e região; submete, aprova e publica; confirma metadados, trilha de auditoria e descoberta nas rotas públicas. O teste passou com 61 assertions.
- Os casos negativos e de estado solicitados foram exercitados pela suíte existente: aprovação sem autorização, rejeição, agendamento, ocultação, mídia sem direitos, nova revisão pós-publicação e remoção/ocultação de destaques.
- A política de direitos de texto continua sem campo ou checklist explícito próprio; fontes/créditos não comprovam licença. Recomenda-se decisão editorial sobre esse controle antes de tratar direitos textuais como plenamente auditáveis.

## Validação

- `composer test`: Pint e PHPStan aprovados (zero erros); suíte completa **171 testes, 165 aprovados, 6 ignorados, 1.124 assertions**.
- Scripts de concorrência editorial e de mídia passaram: escritores/versionamento, colisão e serialização de slug, troca de publicação, sobreposição de destaques; deduplicação de upload, processamento por worker, capa única e conflito de associação.
- `npm run check`: aprovado (102 arquivos formatados; 90 sem avisos de lint). `npm run types:check`: aprovado. `npm run build:ssr`: cliente (2.323 módulos) e SSR (118 módulos) aprovados, com aviso opcional não bloqueante de `fontaine`.
- Revisei as capturas mobile de acervo/região (UX-005) e painel/lista/revisão (UX-006): refluem controles e conteúdo sem overflow horizontal visível. Não foram produzidas capturas UX-007. A navegação por teclado havia sido verificada na rodada UX-006; não foi repetida nesta rodada. Axe/Lighthouse e leitor de tela não foram executados.
- `git diff --check`: aprovado.

## Segurança operacional

- Toda a homologação e as migrations necessárias ocorreram apenas na MariaDB temporária de teste `desgarrados_ux007_test`. Nenhuma migration, seed ou conteúdo foi gravado no banco principal; não houve Docker, push ou deploy.
- A MariaDB efêmera foi encerrada após os testes. Nenhuma dependência nova foi adicionada nesta rodada.

## Arquivos desta rodada

- `tests/Feature/EditorialHomologationTest.php` — fluxo original DEMO integrado.
- Relatório: este arquivo.

---

# DESG-V2-UX-006 — Curadoria e publicação cultural

Data: 2026-10-10, America/Sao_Paulo.

Status: **rodada executada e homologada localmente; painel editorial aprimorado sem migrations nem permissões adicionais.** `correio.md` mudou desde UX-005: SHA-256 atual `1f3e6c3f7653b5cf1f09832b0cd0ab57cbb409e250fde597f09b44dcb500bf1d` (anterior: `f7437435766fee0ddac1e69098adf823ec8be85947c20113f6c185dc8052f25a`).

## Auditoria e melhorias do painel

- O fluxo existente já oferece rascunho, revisão, aprovação, publicação/agendamento, revisão imutável, histórico paginado, fontes, imagens com direitos, taxonomias, vínculos territoriais, destaques, filtros e trilha de auditoria. As rotas continuam protegidas por `PublicationPolicy`/gates; administrador não recebeu bypass editorial e nenhuma permissão foi ampliada.
- O formulário agora mostra orientação contextual por tipo de publicação, explica como preservar versos/estrofes e vincula a instrução ao campo de texto para tecnologia assistiva. Fontes ficam recolhidas por padrão para reduzir altura, são opcionais e exibem lembrete de que crédito não substitui licença/autorização.
- A tela de revisão ganhou resumo imediato de responsável, assinatura pública, município, categoria, regiões e tags; o fluxo exibe orientação correspondente ao estado da revisão. O responsável é carregado apenas na tela administrativa protegida.
- O modelo permanece baseado nas entidades editoriais existentes. Não foram criadas tabelas, enum ou campos de schema.

## Disponibilidade de Node/npm

- Causa encontrada: Node 24.16.0/npm 11.18.0 estão instalados em `~/.nvm`, porém o PATH de subprocessos Composer/não interativos não os continha. `yarn dev` funcionou ao inicializar o Node do NVM e levantou Vite+ em `127.0.0.1:5178` (encerrado após a prova).
- `bin/dev` agora carrega automaticamente a versão NVM `default` (ou alias `node`) quando npm não está no PATH e apresenta erro de configuração legível se Node/npm não existir. A documentação local explica inicialização para comandos diretos `npm run dev`/`yarn dev`.

## Homologação, acessibilidade e evidências

- Capturas do painel geradas em Firefox 157 com usuário e conteúdo explicitamente DEMO apenas na base efêmera `desgarrados_ux006_test`: [lista desktop](docs/evidence/ux-v2-ux-006/editorial-desktop.png) · [lista mobile](docs/evidence/ux-v2-ux-006/editorial-mobile.png) · [cadastro desktop](docs/evidence/ux-v2-ux-006/create-desktop.png) · [cadastro mobile](docs/evidence/ux-v2-ux-006/create-mobile.png) · [revisão desktop](docs/evidence/ux-v2-ux-006/review-desktop.png) · [revisão mobile](docs/evidence/ux-v2-ux-006/review-mobile.png). Os layouts móveis refluem controles e metadados para uma coluna sem overflow horizontal observado.
- Navegação por teclado testada no formulário: Tab moveu o foco para “Toggle sidebar” e o navegador reportou `:focus-visible`. Skip/foco nativo continuam utilizáveis. Não foi executada auditoria automatizada axe/Lighthouse nem teste com leitor de tela; fica como pendência específica.
- Suítes PHP em MariaDB local descartável: `EditorialTest.php`, `EditorialHomologationTest.php`, `MediaTest.php` e `OperationsTest.php` — **82 testes, 80 aprovados, 2 ignorados; 683 assertions**.
- `npm run check:fix` e `npm run check`: aprovados (102 arquivos formatados; 90 arquivos sem avisos de lint). `npm run types:check`: aprovado. `npm run build:ssr`: cliente (2.323 módulos) e SSR (118 módulos) aprovados; aviso opcional não bloqueante sobre `fontaine`.
- Pint e PHPStan: aprovados, PHPStan com zero erros. `git diff --check`: aprovado.

## Limites e segurança operacional

- Migrations executadas somente na base de teste descartável, pelo ciclo de integração. Nenhuma migration, seed, usuário ou conteúdo foi gravado no banco principal; não houve push, deploy, Docker nem nova dependência.
- Os artefatos DEMO e a MariaDB temporária foram usados só para validação local; capturas trazem apenas conteúdo sintético original.
- `correio.md` foi comparado por hash e não foi alterado.

## Arquivos desta rodada

- `bin/dev`, `docs/desenvolvimento-local.md`.
- `app/Http/Controllers/Editorial/PublicationController.php`.
- `resources/js/components/editorial-fields.tsx`, `resources/js/pages/editorial/create.tsx`, `resources/js/pages/editorial/show.tsx`.
- `docs/evidence/ux-v2-ux-006/*.png`.
- Relatório: este arquivo.

---

# DESG-V2-UX-005 — Consolidação da identidade e preparação do acervo

Data: 2026-10-10, America/Sao_Paulo.

Status: **ordem nova executada localmente; filtros, experiências de leitura e homologação desktop/mobile validados em dados sintéticos isolados.** `correio.md` mudou desde UX-004: SHA-256 atual `f7437435766fee0ddac1e69098adf823ec8be85947c20113f6c185dc8052f25a` (anterior: `dbc588da06e5c4e9e72639a9d68ed64537f85c0c7b2fb846f2583f3d03a1d16e`).

## Acervo e leitura

- A listagem pública agora oferece filtros combináveis por tema, expressão/tag, município e região, usando categorias, tags e vínculos territoriais existentes. As opções são limitadas às facetas com conteúdo publicável; valores inválidos não são aceitos. Adicionada cobertura de integração dos filtros.
- Incluído componente compartilhado de leitura: narrativa/memória com leitura editorial, causo com tratamento visual próprio e poema com versos e estrofes preservados. Créditos e vínculos territoriais existentes continuam visíveis. Não foi implementada reprodução de áudio.
- Não foram criadas entidades editoriais duplicadas nem alterado o schema nesta rodada.

## Homologação visual e acessibilidade

- Revisadas as oito telas desktop/mobile atualizadas (home, acervo, leitura de poema e região) em `docs/evidence/ux-v2-ux-005/`. Capturas: [home desktop](docs/evidence/ux-v2-ux-005/home-desktop.png) · [home mobile](docs/evidence/ux-v2-ux-005/home-mobile.png) · [acervo desktop](docs/evidence/ux-v2-ux-005/acervo-desktop.png) · [acervo mobile](docs/evidence/ux-v2-ux-005/acervo-mobile.png) · [poema desktop](docs/evidence/ux-v2-ux-005/poetry-desktop.png) · [poema mobile](docs/evidence/ux-v2-ux-005/poetry-mobile.png) · [região desktop](docs/evidence/ux-v2-ux-005/region-desktop.png) · [região mobile](docs/evidence/ux-v2-ux-005/region-mobile.png).
- As capturas foram feitas a 1440 px e 390 px usando apenas conteúdo original sintético numa MariaDB efêmera em `/tmp`; o mobile empilha filtros, cartões e ações sem overflow horizontal. O texto poético mantém as quebras de versos e separação das estrofes.
- Contraste da paleta principal permanece conforme os cálculos WCAG AA registrados na UX-004; tokens de cor não foram modificados nesta rodada. Skip link, elementos nativos focáveis e estilo global `:focus-visible` foram inspecionados estruturalmente. O teste manual completo de percurso por teclado/leitor de tela e auditoria axe/Lighthouse continuam pendentes: não há WebDriver/geckodriver nem auditoria automatizada disponíveis nesta sessão.

## Administração, direitos e áudio

- Auditoria confirmou o fluxo de revisão/publicação existente com revisões imutáveis e gates de função. `PublicationPolicy` não concede bypass editorial universal ao administrador; as regras de colaborador, autor e editor permanecem. Não foram alteradas permissões.
- Melhorias propostas, sem implementação administrativa nesta etapa: dicas/campos contextuais por tipo cultural, presets editoriais reaproveitando tipos e facetas atuais, checklist verificável de direitos na revisão e melhor descoberta de taxonomias para curadoria. Preservar a matriz atual de papéis.
- Especificação para futura evolução de áudio (sem player ou schema nesta entrega): generalizar a família de mídia e declarar MIME, codec/tamanho/duração; definir armazenamento, validação/processamento, entrega protegida, bloqueio/revogação, transcrição e créditos. Distinguir direitos da composição/letra dos direitos do fonograma/interpretação e exigir base de uso verificável, escopo, validade e evidência; atribuição isolada não basta.

## Banco e validação

- Verificado `migrate:status` no banco principal: **zero migrations pendentes**; todas aparecem como executadas, inclusive `2026_10_10_120000_create_publication_features`. Nenhuma migration foi aplicada ao principal. Para futuras migrations, o plano é backup consistente com `mariadb-dump --single-transaction`, validar restauração em schema isolado, aplicar mudanças incrementais em janela aprovada, verificar e manter rollback compatível/recuperação por backup.
- Testes focados em MariaDB isolada: `EditorialTest.php` + `RegionalCurationTest.php` — **46 testes, 45 aprovados, 1 ignorado; 339 assertions**. Conteúdo demonstrativo só existiu na instância descartável.
- `npm run check:fix` nos arquivos TSX alterados, `npm run types:check`, `npm run build:ssr`, Pint e PHPStan passaram; PHPStan reportou zero erros. Build SSR concluiu (cliente: 2.323 módulos; SSR: 118); aviso de `fontaine` opcional ausente não bloqueia o build.
- Na consolidação do relatório, tentei repetir `npm run check`, mas o comando não pôde iniciar porque `npm`/Node não estão disponíveis neste shell (`npm: command not found`). `git diff --check` passou.
- Nenhuma dependência foi adicionada. Não houve Docker, push, deploy ou escrita no banco principal. A comparação foi feita por hash; `correio.md` não foi editado.

## Arquivos desta rodada

- Backend/modelos e testes: `app/Http/Controllers/Editorial/PublicPublicationController.php`, `app/Models/Category.php`, `app/Models/Tag.php`, `app/Models/Municipality.php`, `tests/Feature/EditorialTest.php`.
- Interface: `resources/js/pages/editorial/feed.tsx`, `resources/js/pages/editorial/publication.tsx`, `resources/js/components/publication-reading-content.tsx`.
- Evidências: `docs/evidence/ux-v2-ux-005/*.png`.
- Relatório: este arquivo.

---

# DESG-V2-UX-004 — Homologação visual e evolução do acervo cultural

Data: 2026-10-10, America/Sao_Paulo.

Status: **homologação desktop/mobile executada com conteúdo sintético isolado; auditoria e proposta de evolução registradas.** `correio.md` mudou desde UX-003: SHA-256 atual `dbc588da06e5c4e9e72639a9d68ed64537f85c0c7b2fb846f2583f3d03a1d16e` (anterior: `50fa4947e47aa8483a522239a8bf412bad1ab9491815a642a914607a9b149e87`).

## Homologação visual

- Investigação do crash: Firefox 157 encerrava com segmentation fault mesmo em `about:blank` dentro do sandbox. Executado fora do sandbox, apenas contra `127.0.0.1`, renderizou normalmente. Não foi necessário instalar navegador alternativo.
- Home, acervo, publicação e página regional responderam `200 OK` e foram capturados em 1440 px desktop e 390 px mobile. As oito imagens estão em `docs/evidence/ux-v2-ux-004/`:
  - Home: [desktop](docs/evidence/ux-v2-ux-004/home-desktop.png) · [mobile](docs/evidence/ux-v2-ux-004/home-mobile.png)
  - Acervo: [desktop](docs/evidence/ux-v2-ux-004/acervo-desktop.png) · [mobile](docs/evidence/ux-v2-ux-004/acervo-mobile.png)
  - Publicação: [desktop](docs/evidence/ux-v2-ux-004/publication-desktop.png) · [mobile](docs/evidence/ux-v2-ux-004/publication-mobile.png)
  - Região: [desktop](docs/evidence/ux-v2-ux-004/region-desktop.png) · [mobile](docs/evidence/ux-v2-ux-004/region-mobile.png)
- Conteúdo demonstrativo foi criado exclusivamente numa instância MariaDB efêmera em `/tmp`, no schema dedicado `desgarrados2_test`, e identificado em títulos, descrições e byline como DEMO/fictício. Após as capturas e testes, servidor e diretório temporário foram encerrados/removidos. Nenhum registro foi criado no banco principal.
- Ajustado na página de publicação o breadcrumb de “Histórias” para “Acervo”, alinhando-o ao menu, e o retorno de “Todas as histórias” para “Todo o acervo”. Metadado Open Graph do feed também foi alinhado ao vocabulário cultural/territorial.

## Acessibilidade e responsividade

- Inspeção visual em 1440 × 1800 e 390 × 1700: navegação não transborda; menu móvel permanece compacto; leitura, cards, navegação regional e metadados refluem em coluna nas telas estreitas.
- Contraste calculado pela fórmula WCAG para os pares principais de texto/fundo: tinta/papel claro 10,00:1; texto secundário/papel 5,75:1; couro/papel 5,50:1; tinta/papel escuro 13,02:1; texto secundário/papel escuro 9,42:1; couro/papel escuro 7,90:1; papel sobre mate 10,00:1 (claro) e 13,02:1 (escuro). Todos superam 4,5:1. Isto cobre as combinações principais da paleta, não constitui certificação completa de cada estado/elemento.
- Verificados estruturalmente skip link, links e summary nativos focáveis e regra visual `:focus-visible` global. A simulação interativa de percurso por Tab/leitor de tela ficou pendente: não há WebDriver/geckodriver instalado. Também não foi executado axe/Lighthouse.

## Auditoria e proposta de evolução

- Modelo atual: `Publication` já liga revisão versionada, categoria, tags, município, regiões e mídias de revisão. Tipos cobrem artigos/notícias/crônicas, causos, história cultural, histórias pessoais/migração e memórias. As rotas públicas só expõem três seções e filtros territoriais. Revisão é corpo textual simples; `MediaAsset` e seu processamento são orientados a imagem. Fontes registram título/URL/atribuição/data, sem autorização autoral para o texto.
- Recomendação: conservar `Publication`/`PublicationRevision` e seus vínculos territoriais como registro editorial comum; evitar tabelas separadas para poesia, causos, música ou vocabulário. Manter os tipos já úteis (causo, memória, narrativa, história cultural) e, após decisão editorial, acrescentar apenas famílias com experiência realmente distinta — por exemplo obra literária e patrimônio musical. Poema/verso, tradição e expressão vocabular podem ser facetas controladas de categoria/tag, sem um enum para cada subgênero. Tornar as facetas descobríveis por tema, município e região.
- Experiências: leitura literária com autoria, dedicatória/contexto e preservação de versos/estrofes; causo com narrador, contexto e transcrição legível; patrimônio musical com player para áudio autorizado, transcrição opcional, intérpretes/compositores e créditos; memória/tradição com contexto e vínculo territorial. A página principal continua composta por percursos editoriais e curadoria, nunca por ordenação cronológica genérica.
- Para áudio, evoluir a abstração de mídia existente para declarar tipo MIME/família e suportar armazenamento, validação, entrega, bloqueio e revogação de áudio; reutilizar o vínculo da revisão quando viável, sem tratar áudio como imagem nem criar um segundo catálogo paralelo.
- Direitos devem ser registrados por revisão/obra e por gravação quando aplicável: titular/autoria, situação (domínio público, licença identificada, autorização ou restrição), identificador/URL da licença, escopo e território autorizados, evidência da autorização, validade, revogação e trilha de auditoria. Em música, distinguir direitos da composição/letra dos direitos do fonograma/interpretação. Atribuição/crédito não substitui licença. Publicação deve exigir base de uso verificável; revogação deve permitir retirada rápida da obra e de derivados.
- A proposta não implementa mudanças de schema; exige critérios editoriais/jurídicos e autorização explícita antes de migrations incrementais.

## Validação, escopo e pendências

- Testes MariaDB: `EditorialTest.php` + `RegionalCurationTest.php` aprovados na instância efêmera: 45 testes, 44 aprovados, 1 ignorado, 312 assertions.
- `npm run check`: aprovado — 101 arquivos formatados, 89 sem avisos de lint. `npm run types:check`: aprovado. `npm run build:ssr`: build cliente (2.322 módulos) e SSR (117 módulos) aprovados; aviso não bloqueante de `fontaine` opcional ausente.
- `correio.md` foi apenas lido; não foi editado. Nenhuma migration foi executada na base principal (`desgarrados`), nem houve push/deploy. A migration inicial foi executada somente no schema descartável necessário à homologação visual; testes também rodaram apenas nele. Pendência operacional de DESG-V2-008 permanece sem alteração.
- Pendências: teste manual de teclado e tecnologia assistiva com navegador controlável; auditoria automatizada completa de acessibilidade; revisão da proposta de direitos com responsáveis; decisão de produto sobre famílias editoriais; homologar áudio apenas após direitos/infraestrutura e decisão específica.

## Arquivos desta rodada

- Frontend: `resources/js/pages/editorial/publication.tsx`, `resources/js/pages/editorial/feed.tsx`.
- Evidências: `docs/evidence/ux-v2-ux-004/*.png`.
- Relatório: `executed.md`.

---

# DESG-V2-UX-003 — Acervo cultural e identidade territorial

Data: 2026-10-10, America/Sao_Paulo.

Status: **ordem nova executada parcialmente na camada visual; validações de código aprovadas.** `correio.md` mudou desde a última ordem UX-002: SHA-256 atual `50fa4947e47aa8483a522239a8bf412bad1ab9491815a642a914607a9b149e87` (anterior: `2e080bcf24d0a01c1bba8a52c3f4769cfeedb8b5065035a68a22d2d4914e2756`).

## Resultado visual

- Home agora mantém o título solicitado “Há lugares que seguem com a gente.”, reduz o respiro vertical em telas pequenas e substitui o grande vazio do destaque sem conteúdo por uma ilustração SVG original. A arte declara explicitamente que não representa um lugar real; não foram usadas fotos ou publicações inventadas.
- A descoberta da home parte de três caminhos editoriais — vidas e travessias, causos, saberes e tradições —, sem transformar a página em feed cronológico. Blocos de conteúdo só aparecem quando há publicações reais. A área territorial vazia oferece busca e contagens existentes.
- O convite à comunidade deixa claro que as publicações passam por curadoria; não sugere que o cadastro comum habilita envio direto.
- Navegação chama o conjunto de “Acervo”. O feed usa linguagem e hierarquia da identidade pública e seu estado vazio oferece rotas para continuar a descoberta, sem repetir um grande placeholder editorial.
- Mantidos rotas, SSR/SEO e contratos existentes; nenhuma migration, alteração de banco, controller/backend, push ou deploy foi executado.

## Auditoria do modelo editorial e direitos

- Tipos disponíveis atualmente: `article`, `news`, `chronicle`, `causo`, `cultural_history`, `personal_story`, `migration_story` e `memory`. O portal os agrupa nas trilhas narrativas, causos e história cultural; não há tipos/experiências explícitos de literatura ou música nem navegação pública completa por categorias/tags.
- Revisões guardam título, resumo e corpo textual simples. Isso acomoda prosa e quebras de linha, mas não oferece blocos editoriais próprios para poema, letra, áudio ou experiência de escuta. Mídias são imagens; não há player/formato de áudio.
- Fontes registram título, URL, atribuição e data de acesso. Ativos de imagem têm metadados de direitos/licença, mas atribuição isolada não concede autorização para republicar poemas ou letras. Antes de ampliar o acervo, recomenda-se definir fluxo de autorização/licença e como registrar direitos para textos e áudio. Uma futura revisão deve avaliar tipos/tags e formatos com a equipe editorial, sem duplicar esquemas nem presumir permissão autoral.
- A home mantém apenas formatos já sustentados. Literatura, música e áudio ficam como recomendação de produto/modelagem, não como conteúdo prometido.

## Validação e limitações

- `npm run check`: aprovado — 101 arquivos formatados, 89 sem avisos de lint.
- `npm run types:check`: aprovado.
- `npm run build:ssr`: build cliente (2.322 módulos) e SSR (117 módulos) aprovados. Aviso não bloqueante: pacote opcional `fontaine` não instalado.
- Capturas desktop/mobile pedidas pela ordem não puderam ser atualizadas: Firefox headless encerrou com segmentation fault antes de renderizar. A pasta `docs/evidence/ux-v2-ux-003/` foi criada, mas não contém capturas válidas. Também não existe região ativa local para validar uma página regional com conteúdo autêntico. Nenhuma imagem anterior foi reapresentada como se fosse desta rodada.
- Suítes PHP e migrations não foram executadas; não houve escrita no banco. A pendência operacional de DESG-V2-008 permanece inalterada.

## Arquivos desta rodada

- Modificados nesta entrega visual: `resources/js/components/regional-hero-artwork.tsx`, `resources/js/pages/welcome.tsx`, `resources/js/pages/editorial/feed.tsx`, `resources/js/layouts/public-layout.tsx` e este relatório.
- `correio.md` foi apenas lido para comparação; seu conteúdo não foi alterado.

---

# DESG-V2-UX-002 — Primeira implementação UX/UI

Data: 2026-10-10, America/Sao_Paulo.

Status: **implementação visual local concluída nas páginas públicas prioritárias.** O `correio.md` mudou desde DESG-V2-UX-001; SHA-256 da ordem: `2e080bcf24d0a01c1bba8a52c3f4769cfeedb8b5065035a68a22d2d4914e2756`.

## Implementação

- Centralizada paleta semântica claro/escuro em `resources/css/app.css`: papel, superfície, tinta, mate, couro, vinho e divisórias. Tipografia editorial usa Georgia local como serif e preserva Instrument Sans para corpo; não foi adicionada dependência externa.
- `PublicLayout` ganhou cabeçalho mais compacto, navegação reduzida, menu móvel nativo por disclosure, link destacado “Buscar lugar” e foco de teclado evidente. A descoberta usa o fluxo funcional atual: escolher estado e então buscar município.
- Home reorganizada em torno de pertencimento, busca territorial, destaque real, regiões, trilhas editoriais e convite territorial. Usa somente dados existentes; quando ausentes, apresenta empty states editoriais.
- Página regional ganhou breadcrumb, resumo, atalhos locais, áreas de destaque/histórias/municípios com paginação e navegação para outras áreas territoriais.
- Página de publicação ganhou breadcrumb, hierarquia tipográfica editorial, coluna de leitura, identificação territorial e percurso para outras histórias ligadas às mesmas regiões. Mantidos conteúdo, fontes/créditos, canonical, Open Graph e mídia autorizada.
- Criados componentes compartilhados `PublicSectionHeading`, `PublicEmptyState` e `TerritoryFinder`; `StoryCard` agora acomoda destaque fotográfico e ausência de capa sem abrir espaço vazio.
- Não houve alteração em rotas/controllers, contratos, regras de visibilidade, direitos de mídia ou banco. Administração permanece fora do escopo desta rodada.

## Arquivos desta entrega

- Modificados: `resources/css/app.css`, `resources/js/layouts/public-layout.tsx`, `resources/js/components/story-card.tsx`, `resources/js/pages/welcome.tsx`, `resources/js/pages/territory/region.tsx`, `resources/js/pages/editorial/publication.tsx`.
- Criados: `resources/js/components/public-empty-state.tsx`, `resources/js/components/public-section-heading.tsx`, `resources/js/components/territory-finder.tsx`, `docs/evidence/ux-v2-ux-002/home-desktop.png`, `docs/evidence/ux-v2-ux-002/home-mobile.png`.

## Capturas e validação

- [Home desktop, 1440 × 1400](docs/evidence/ux-v2-ux-002/home-desktop.png) e [home mobile, 390 × 844](docs/evidence/ux-v2-ux-002/home-mobile.png), renderizadas em Firefox headless usando o schema local de testes isolado. Como ele não contém publicações/regiões de produção, as imagens evidenciam os estados vazios reais — nenhum conteúdo inventado foi usado.
- Não há região ativa disponível na base local para gerar captura regional com dados autênticos. O componente regional compila, mas a validação visual dinâmica da região fica pendente de conteúdo elegível.
- `npm run check`: **100 arquivos formatados; 88 sem avisos de lint**.
- `npm run types:check`: **aprovado**.
- `npm run build:ssr`: **build cliente (2.321 módulos) e SSR (116 módulos) aprovados**. O pipeline emitiu avisos não bloqueantes sobre `fontaine` opcional ausente e sourcemap de transformação Inertia.
- Navegação desktop/mobile exercitada na captura da home; o disclosure móvel e links apontam para as rotas já existentes.
- Não foi reexecutada suíte PHP, nem migration. A pendência operacional da DESG-V2-008 permanece como registrada anteriormente e não é declarada resolvida por esta entrega visual. Nenhuma operação destrutiva, push ou deploy ocorreu.

## Próxima rodada

Validar a página regional com ao menos uma região ativa e conteúdo elegível; conferir contraste WCAG AA com ferramenta dedicada e testar teclado/leitor de tela; revisar fotos reais autorizadas em desktop/mobile; avaliar estados vazios e navegação entre regiões. A harmonização administrativa permanece separada.

---

# DESG-V2-UX-001 — Identidade visual e experiência do portal

Data: 2026-10-10, America/Sao_Paulo.

Status: **auditoria e protótipo responsivo entregues para avaliação; nenhuma reformulação ampla foi aplicada à interface de produção.** O `correio.md` mudou desde a ordem anterior; SHA-256: `5ec97ddf60c2009baf9d7996dc41429947a71cbdf6da6be73793d1534dd13493`.

## Auditoria e direção

- Auditados layouts, home, páginas territorial/regional, feed, publicação, navegação, componentes editoriais e CSS global.
- Aproveitar: Laravel/React/Inertia com SSR; canonical/SEO/Open Graph nas páginas públicas; `PublicLayout`, skip link, tema claro/escuro, `StoryCard`, `StoryFigure`, páginas territoriais, paginação, estados vazios e regras atuais de mídia/visibilidade.
- Oportunidades: cores públicas aplicadas inline sem escala semântica comum; cabeçalho se reorganiza por wrap em mobile e não oferece busca pública global; trilhas e cards têm ritmo uniforme; página regional não oferece atalhos de seção; foco/contraste/alvos de toque precisam de validação sistemática.
- Proposta Regional Contemporâneo: estética editorial de arquivo vivo, paleta papel/erva-mate/couro/vinho, serif editorial + Instrument Sans existente, respiro e hierarquia territorial. Paleta, medidas e componentes sugeridos estão especificados em `docs/ux-001-auditoria-proposta.md`; contraste WCAG AA ainda deve ser validado antes de adoção.

## Protótipos e escopo

- Protótipo navegável desktop/mobile de home e página regional: `docs/prototypes/ux-001-publico.html`. Inclui variação escura, navegação por âncoras, layout móvel, foco visível e `prefers-reduced-motion`.
- Todo o conteúdo é explicitamente demonstrativo; não representa histórias, lugares ou vínculos reais. Busca e links são apenas affordances visuais e não chamam o backend.
- A proposta, auditoria, composição das páginas e plano incremental estão em `docs/ux-001-auditoria-proposta.md`.
- O protótipo é artefato isolado, não uma página/rota React de produção. Assim não altera SSR/SEO, contratos, banco, curadoria, mídia nem administração; a harmonização administrativa permanece para etapa posterior. A homologação pendente da DESG-V2-008 segue sem ser contornada.

## Validação

| Verificação | Resultado |
| --- | --- |
| `npm run check` | Aprovado: 97 arquivos formatados; 85 sem avisos de lint |
| `npm run types:check` | Aprovado |
| Estrutura do protótipo | Aprovada: 11 IDs, 24 links internos válidos, breakpoints mobile e regra de movimento reduzido |
| `git diff --check` | Aprovado para arquivos do projeto, excluindo o espaço final já existente em `correio.md` |
| Visual em navegador/headless | Não executado: não há navegador headless instalado/disponível nesta sessão; HTML pode ser aberto localmente para revisão |

## Próxima etapa e limite

Aguardar aprovação da direção/protótipos antes de implementar mudanças visuais amplas. Após aprovação, validar contraste, teclado/leitor de tela e larguras 360/768/1280 px; então implementar tokens e masthead público, evoluindo home/região com `StoryCard`/`StoryFigure`, sem alterar políticas atuais. Não foram executadas migrations, deploy ou push.

---

# DESG-V2-008.1 — Simplificação do banco de dados local

Data: 2026-10-10, America/Sao_Paulo.

Status: **ordem executada e homologada localmente em MariaDB 11.8.6.** O `correio.md` mudou em relação à versão anterior; SHA-256 desta ordem: `28352dfbff05d80ae57eaac8a048f6cbe761acfcc8f4990c3043c8193536d897`.

## Restrições removidas e proteção de testes

- Removida das migrations editorial e de mídia a exigência de driver `mariadb` e dos nomes fixos `desgarrados2` / `desgarrados2_test`. A migration de destaques também não impõe essas restrições. Schema Builder do Laravel e constraints existentes foram mantidos.
- `phpunit.xml` não força conexão, host, credenciais ou nome de banco. O bootstrap dos testes cria uma conexão `testing` a partir de `TEST_DB_*`, aceita MySQL/MariaDB e valida host local, separação do banco principal e identidade efetiva antes de `RefreshDatabase`.
- As migrations e a suíte foram executadas somente no schema local independente `desgarrados_local_test`, criado vazio para esta finalidade. O banco principal `desgarrados` não recebeu migrations nem operações de limpeza. O schema de teste foi mantido para repetição local.
- Não há nome fixo de banco de teste no código. `desgarrados_local_test` é apenas o schema usado nesta execução.
- Adicionados os parâmetros opcionais documentados em `.env.example` e a seção “Testes de integração” em `docs/desenvolvimento-local.md`.

## Verificações

| Verificação | Resultado |
| --- | --- |
| Conexão/driver efetivo | MariaDB 11.8.6 local; identidade conferida antes das migrations de teste |
| Suíte PHP sem SSR | **169 testes, 163 aprovados, 6 ignorados; 1.036 assertions** |
| Suíte PHP com SSR ativo | **169 testes, 165 aprovados, 4 ignorados; 1.051 assertions** |
| Concorrência editorial | **10 verificações aprovadas**, incluindo disputa simultânea por destaque |
| Concorrência de mídia | **9 verificações aprovadas** |
| PHP syntax | Aprovado nos arquivos PHP alterados |
| Pint | Aprovado |
| `npm run check` | 95 arquivos formatados; 85 sem avisos de lint |
| `npm run types:check` | Aprovado |
| PHPStan | Aprovado, zero erros |
| `composer validate --no-check-publish` | Aprovado |

## Compatibilidade e limites

O código agora usa os drivers `mysql` ou `mariadb` configurados pelo Laravel, e não há dependência de Docker. A homologação de migrations e testes foi feita no MariaDB local; este host não tinha servidor MySQL 8 para uma segunda execução integrada, portanto MySQL 8 permanece sem verificação runtime nesta rodada. A DESG-V2-009 não foi iniciada; não houve push nem deploy.

---

# Relatório anterior — DESG-V2-008.1 — Desbloqueio MariaDB e homologação integrada

Data: 2026-10-10, America/Sao_Paulo.

Status: **operações dependentes do MariaDB bloqueadas pela ausência de credencial autorizada.** A implementação da DESG-V2-008 foi preservada integralmente. Não houve migration, alteração de banco, criação ou promoção de usuário, Docker, deploy ou push.

## 1. Comparação e execução da nova ordem

O `correio.md` mudou novamente: agora contém a DESG-V2-008.1, SHA-256 `56fe61b4e6e75bdad96e60d4b0a562eab1648262a2284538a3ee0298ecc179b0`. A ordem determina verificar uma fonte local autorizada de credencial e, se ela não existir, interromper apenas as operações autenticadas e solicitar `DB_PASSWORD` ao operador.

Verificações realizadas somente dentro deste projeto, sem imprimir valores:

| Fonte autorizada | Resultado |
| --- | --- |
| `.env` | Ausente |
| `.env.testing` | Ausente |
| Outros arquivos `.env*` | Somente `.env.example`, sem credencial preenchida |
| Variável de processo `DB_PASSWORD` | Ausente |
| Variável de processo `DATABASE_URL` | Ausente |
| `bootstrap/cache/config.php` | Ausente |

Não foram pesquisados arquivos de outros projetos, histórico de shell, gerenciadores externos ou configurações globais. Também não houve tentativa de descobrir, recuperar ou redefinir senha.

## 2. Operações interrompidas

Sem uma senha autorizada para o usuário restrito `desgarrados`, não é possível confirmar a conexão PDO com MariaDB nem verificar a identidade de `desgarrados2_test`. Por isso, conforme a ordem, ficaram interrompidos:

1. migrations no banco de testes;
2. suíte PHP completa;
3. testes concorrentes;
4. homologação SSR integrada;
5. repetição dos testes de estabilidade.

O banco principal `desgarrados2` não foi acessado nem modificado. A etapa de backup, plano incremental e pedido de autorização para a migration principal só poderá ocorrer depois que toda a homologação em `desgarrados2_test` estiver aprovada.

## 3. Desbloqueio necessário

O operador deve disponibilizar `DB_PASSWORD` com segurança ao processo, sem gravá-la em commit ou relatório. Após isso, a execução deve continuar na sequência determinada pela DESG-V2-008.1. A DESG-V2-008 permanece **não encerrada operacionalmente**, e a DESG-V2-009 não foi iniciada.

---

# Relatório preservado — DESG-V2-008

# DESG-V2-008 — Curadoria regional, destaques e consolidação editorial

Data: 2026-10-10, America/Sao_Paulo.

Status: **implementação local concluída; validação integrada no MariaDB pendente de credencial do banco de testes.** Nenhum deploy, push, Docker, alteração global do host ou operação destrutiva foi executado. A migration não foi aplicada ao banco principal.

## 1. Comparação do correio

O `correio.md` está diferente da versão anterior no Git: substituiu a DESG-V2-007 pela DESG-V2-008. SHA-256 da ordem executada: `18ee5192eb11ce36c5af28c55c3e1ab60027fa927524815094314dcfbf3cabef`. A nova ordem foi executada até o limite permitido pelo ambiente atual.

## 2. Fase A — ajustes da entrega anterior

- O limite editorial permanece em 10 MB na aplicação. `php/conf.d/desgarrados-uploads.ini` foi validado com `upload_max_filesize=12M` e `post_max_size=16M`.
- `composer dev` agora é o único ponto de entrada: chama `bin/dev`, inclui automaticamente o `.ini` local e inicia o comando nativo do Laravel, que já registra servidor, fila, scheduler, logs e Vite. A operação está documentada em `docs/desenvolvimento-local.md`.
- npm e `package-lock.json` continuam oficiais. `npm install` apenas sincronizou `node_modules`; o lockfile não teve alteração final.
- Derivados públicos passaram de cache compartilhado por 300 segundos para `max-age=0, must-revalidate, public`. Cada nova requisição revalida a autorização; bloqueio, ocultação ou revogação de direitos produz 404 na requisição seguinte.
- Prévias e originais privados permanecem `private, no-store`.
- `RevisionMediaProvider` foi removido depois de confirmar por busca global que não possuía implementação, binding ou consumidor. O fluxo real continua centralizado em `RevisionMediaManager`, `MediaPresenter` e nos relacionamentos de revisão.
- Dockerfile, `.dockerignore`, `docker-entrypoint.sh`, `docker/` e `deploy/` ficaram intocados.

## 3. Curadoria de regiões

- Nova administração em `/administracao/editorial/regioes`, exclusiva para Editor verificado.
- Cadastro e edição de nome, slug estável, descrição, classificação cultural/geográfica e estado ativo/inativo.
- Busca e associação manual de múltiplos municípios por região. A operação altera somente `municipality_region`; nome, slug, código IBGE, estado e demais dados oficiais dos municípios não são escritos.
- Publicações continuam relacionadas a múltiplas regiões pelo fluxo de metadados existente.
- Região inativa responde 404 na página pública.
- Nova página `/regioes/{slug}` com identidade, descrição, municípios paginados, destaques e publicações recentes paginadas.

## 4. Destaques editoriais

Migration incremental `2026_10_10_120000_create_publication_features.php`:

- `publication_features`: publicação, posição de 1 a 12, início, fim opcional, região opcional, curador e timestamps;
- índices por agenda/escopo e por publicação;
- checks para posição e período válido;
- `publication_feature_locks`: mutex persistente por escopo e posição, usado com `SELECT ... FOR UPDATE` para impedir duas inserções concorrentes no mesmo intervalo.

Regras implementadas:

- intervalos semiabertos: `[início, fim)`, permitindo períodos adjacentes;
- a mesma posição não pode se sobrepor dentro do mesmo escopo; home geral e regiões são escopos independentes;
- somente publicação aprovada, publicada, pública e já vencida pode ser destacada;
- destaque regional exige relação prévia entre publicação e região ativa;
- mídia bloqueada, sem direitos ou não processada impede criação/edição do destaque;
- toda leitura pública revalida visibilidade e mídia, portanto ocultação, arquivamento, exclusão lógica, data futura ou perda de elegibilidade retiram o destaque sem depender de cache;
- criação, edição e remoção são auditadas em `audit_entries` (`feature_created`, `feature_updated`, `feature_deleted`), com responsável, estado anterior e posterior;
- administração em `/administracao/editorial/destaques`, com home/região, posição e período.

## 5. Home, regiões, SEO e desempenho

- A home agora usa apenas publicações reais aprovadas: destaque principal, demais destaques, histórias recentes, causos, cultura e tradições, regiões em evidência e convite territorial.
- Ausência de conteúdo gera estados vazios editoriais, sem fixtures ou texto apresentado como publicação real.
- Componentes são responsivos, compatíveis com modo escuro, navegação por links, títulos semânticos e imagens com as garantias de dimensão/alt já existentes.
- Home, feed, publicação e região possuem título, descrição, canonical e Open Graph; a imagem OG só aparece quando existe capa publicável.
- HTML essencial permanece no caminho SSR e o build SSR concluiu.
- Consultas públicas usam eager loading e limites/paginação. Não foi adicionado cache de consulta: neste volume, a invalidação imediata e segura é mais importante que um cache adicional.
- Municípios e publicações regionais têm paginadores independentes.

## 6. Arquivos principais

Criados:

- `app/Actions/Editorial/ManagePublicationFeatures.php`
- `app/Editorial/PublicEditorialPresenter.php`
- `app/Http/Controllers/Editorial/PublicationFeatureController.php`
- `app/Http/Controllers/Editorial/RegionController.php`
- `app/Http/Controllers/RegionalPageController.php`
- `app/Models/PublicationFeature.php`
- `database/migrations/2026_10_10_120000_create_publication_features.php`
- `bin/dev`
- `docs/desenvolvimento-local.md`
- `resources/js/components/story-card.tsx`
- `resources/js/pages/editorial/features.tsx`
- `resources/js/pages/editorial/regions.tsx`
- `resources/js/pages/territory/region.tsx`
- `tests/Feature/RegionalCurationTest.php`

Modificados: modelos `Publication`/`Region`, controllers públicos, rotas, home/feed editorial, configuração e teste de cache de mídia, `composer.json` e teste concorrente editorial. Removido: `app/Contracts/RevisionMediaProvider.php`.

## 7. Autorização

| Operação | Autor/Colaborador | Editor | Administrador sem Editor | Visitante |
| --- | --- | --- | --- | --- |
| Administrar regiões | Não | Sim | Não | Não |
| Associar municípios | Não | Sim | Não | Não |
| Administrar destaques | Não | Sim | Não | Não |
| Ver região ativa e conteúdo elegível | Sim | Sim | Sim | Sim |
| Ver região inativa ou conteúdo oculto | Não | Não pela rota pública | Não | Não |

O projeto preserva a regra existente de não conceder bypass editorial ao papel Administrador.

## 8. Testes e validações

| Verificação | Resultado |
| --- | --- |
| `vendor/bin/phpstan analyse --no-progress --memory-limit=1G` | **Aprovado, zero erros** |
| `vendor/bin/pint --test --format agent` | **Aprovado** |
| `npm run check` | **95 arquivos formatados; 85 sem avisos de lint** |
| `npm run types:check` | **Aprovado** |
| `npm run build:ssr` | **Aprovado: cliente 2.318 módulos; SSR 113 módulos** |
| `composer validate --no-check-publish` | **Aprovado** |
| `php artisan route:list --except-vendor` | **45 rotas; novas rotas de região e destaques registradas** |
| `bash -n bin/dev` | **Aprovado** |
| PHP com `.ini` local | **12M / 16M**, suficiente para regra de 10 MB |
| Header Symfony da mídia pública | **`max-age=0, must-revalidate, public`** |
| `git diff --check` | **Aprovado** |
| Arquivos Docker | **Sem diferenças** |

Foram acrescentados seis testes de curadoria regional, cobrindo autorização, preservação de dados oficiais, auditoria, sobreposição/adjacência, escopos independentes, inelegibilidade, página regional e retirada imediata da home. `tests/Support/editorial-concurrency.php` passou a disputar a mesma posição regional em dois processos, esperando exatamente um vencedor.

## 9. Pendência de infraestrutura

A suíte PHP não pôde chegar à primeira asserção. O workspace não contém `.env`; o `phpunit.xml` força MariaDB em `127.0.0.1`, banco `desgarrados2_test`, usuário `desgarrados`, mas não fornece `DB_PASSWORD`. Resultado: `SQLSTATE[HY000] [1045] Access denied ... (using password: NO)`. O acesso root por socket também exige senha.

Por segurança, não foi criada nem promovida conta, não foram buscadas credenciais fora deste projeto e não foi usado SQLite para contornar a proteção MariaDB das migrations. Assim que `DB_PASSWORD` do usuário restrito for fornecida ao processo, ainda faltam executar:

1. migration e suíte completa em `desgarrados2_test`;
2. testes concorrentes;
3. suíte com renderer SSR ativo;
4. migration incremental no `desgarrados2`, sem `migrate:fresh`, somente depois de backup e confirmação dos testes.

**A DESG-V2-008 não deve ser declarada operacionalmente encerrada até essas quatro validações. Não iniciar a etapa seguinte.**
