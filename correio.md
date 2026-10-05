# DESG-V2-004 — Fundação territorial e administrativa

**Projeto:** Desgarrados 2  
**Status:** autorizado para implementação controlada.

## Objetivo

Implementar o primeiro incremento funcional da arquitetura aprovada na DESG-V2-003, preservando integralmente a fundação existente.

## 1. Território

- Criar migrations para `states`, `municipalities`, `regions` e `municipality_region`.
- Implementar Models, relacionamentos, índices e restrições.
- Criar importador idempotente utilizando dados oficiais do IBGE.
- Estruturar o território nacional sem limitar o schema ao Sul.
- Priorizar Rio Grande do Sul nos dados e na experiência editorial inicial.
- Permitir regiões culturais sobrepostas e associações de municípios a múltiplas regiões.
- Preservar registros já referenciados durante futuras sincronizações.

## 2. Administração e acesso

- Implementar `roles` e `role_user`.
- Definir papéis conforme a matriz aprovada.
- Implementar Gates e Policies necessários.
- Não criar usuários, administradores ou senhas automaticamente.
- Não permitir bypass universal de privacidade.
- Auditar concessões e revogações de papéis.
- Proteger a existência de pelo menos um administrador ativo.

## 3. Navegação inicial

Implementar as primeiras páginas funcionais:

- Página pública inicial.
- Exploração por estados.
- Exploração por municípios.
- Página territorial **Minha terra**, sem persistir dados pessoais automaticamente.
- Estrutura de navegação administrativa protegida.

Utilizar Laravel, Inertia, React e TypeScript já instalados. Priorizar componentes reutilizáveis, responsividade e acessibilidade.

A identidade visual inicial deve refletir o conceito **“Nossas raízes seguem conosco”**, evitando aparência de painel administrativo genérico nas páginas públicas.

## 4. Segurança e integridade

- Utilizar exclusivamente os bancos autorizados `desgarrados2` e `desgarrados2_test`.
- Preservar a proteção de testes existente.
- Não executar `migrate:fresh` no banco principal.
- Não consultar ou reutilizar projetos externos.
- Não instalar dependências sem necessidade justificada.
- Garantir integridade referencial e isolamento de permissões.

## 5. Verificações

Executar:

- Testes Pest e testes de integridade territorial.
- Validação de permissões.
- PHPStan e Pint.
- TypeScript.
- Build do frontend.
- Verificação SSR, quando aplicável.
- Homologação visual em navegador, caso disponível.

## 6. Entrega

Registrar no `executed.md`:

1. Arquivos alterados e migrations criadas.
2. Estrutura final de tabelas, índices e FKs.
3. Resultado da importação do IBGE.
4. Rotas e páginas funcionais.
5. Evidências dos testes.
6. Pendências e riscos.
7. Recomendações para a DESG-V2-005.

**Não iniciar o módulo editorial completo nem a comunidade nesta etapa.**

Não declarar como concluída nenhuma validação que não tenha sido efetivamente executada.