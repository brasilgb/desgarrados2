# DESG-V2-007 — Mídia editorial versionada

**Status:** autorizado para implementação local.

## Objetivo

Implementar o gerenciamento completo de imagens editoriais do Desgarrados 2, integrado ao sistema de publicações, revisões e aprovação existente.

A mídia deverá respeitar a identidade do portal: histórias de quem partiu, memórias da terra natal, cultura, tradições, causos e paisagens regionais.

## Fase A — Organização do ambiente

1. Padronizar npm e `package-lock.json`.
2. Verificar o conteúdo de `yarn.lock` antes de descartá-lo; não excluir outros arquivos não rastreados.
3. Manter os arquivos Docker existentes intocados, reservados à futura VPS.
4. Preservar `desgarrados2` e `desgarrados2_test`.
5. Não modificar configurações globais do host sem autorização.

## Fase B — Modelagem de mídia

Implementar as tabelas previstas na arquitetura:

- `media_assets`: registro do arquivo, proprietário, MIME, dimensões, tamanho, estado de processamento e direitos de uso.
- `revision_media`: associação entre imagens e revisões, posição, finalidade, texto alternativo, legenda e crédito.

Usar migrations incrementais, constraints, índices e chaves estrangeiras adequados ao MariaDB.

O mesmo arquivo poderá ser reutilizado em diferentes revisões sem duplicação desnecessária.

## Fase C — Upload e processamento

Implementar upload autenticado e autorizado, inicialmente para imagens JPEG, PNG e WebP.

Requisitos:

- Limite inicial de 10 MB.
- Verificar conteúdo real e dimensões.
- Rejeitar SVG, HTML e arquivos inválidos.
- Gerar nomes internos aleatórios.
- Guardar originais em armazenamento privado.
- Remover metadados EXIF dos derivados.
- Gerar variantes otimizadas para capa, cartões e conteúdo.
- Preservar proporções e evitar ampliação desnecessária.
- Executar processamento de modo seguro e idempotente.

Utilizar inicialmente GD, disponível no host. Avaliar necessidade de bibliotecas adicionais antes de instalá-las.

## Fase D — Integração editorial

Na redação, adicionar:

1. Upload de imagens.
2. Seleção de capa.
3. Biblioteca de imagens autorizadas.
4. Pré-visualização da imagem.
5. Legenda, crédito e texto alternativo.
6. Registro de direitos de uso.
7. Organização das imagens da publicação.
8. Remoção de associação com uma revisão.

A interface deve utilizar React, Inertia e TypeScript, mantendo o padrão visual atual.

**Regra obrigatória:** cada revisão possui seu conjunto de imagens aprovado. A criação de uma nova revisão não poderá modificar silenciosamente a mídia da versão já publicada.

## Fase E — Segurança e publicação

- Imagens em elaboração permanecem privadas.
- Imagens rejeitadas ou bloqueadas não ficam acessíveis publicamente.
- Somente derivados de mídias aprovadas podem ser publicados.
- Ocultação da publicação deve retirar o acesso público à mídia associada.
- Impedir acesso indevido por ID, caminho ou URL.
- Não disponibilizar originais privados por links diretos.
- Registrar operações relevantes em auditoria.
- Exigir informações de direitos de uso antes da publicação.

## Fase F — Experiência pública

Integrar as imagens às páginas de histórias, causos, memórias e cultura.

Implementar:

- Capa responsiva.
- Imagens no conteúdo editorial.
- Legendas e créditos.
- Texto alternativo para acessibilidade.
- Dimensões explícitas para evitar deslocamentos de layout.
- Carregamento otimizado.
- Metadados Open Graph com imagem aprovada, quando disponível.
- Renderização compatível com SSR.

Evitar galerias complexas ou editores visuais avançados nesta etapa.

## Fase G — Testes

Validar:

- Upload válido e inválido.
- MIME real.
- Limites de tamanho e dimensões.
- Remoção de EXIF.
- Processamento das variantes.
- Direitos de uso.
- Autorização por papel.
- Associação por revisão.
- Rejeição e aprovação.
- Publicação e ocultação.
- SSR.
- Privacidade dos originais.
- Concorrência.
- Exclusão e limpeza segura de arquivos órfãos.

Executar suíte completa, PHPStan, Pint, TypeScript e build SSR.

Todos os fixtures deverão utilizar exclusivamente `desgarrados2_test`, inclusive arquivos de mídia em armazenamento de teste isolado.

## Restrições

Não iniciar comunidade, comentários, reações, perfis públicos, publicidade ou monetização.

Não executar Docker.

Não realizar push nem deploy.

Não acessar outros projetos.

Não modificar a infraestrutura VPS.

Não realizar operações destrutivas no banco principal.

## Entrega

Atualizar `executed.md` com:

- Arquivos criados e modificados.
- Migrations e alterações de schema.
- Fluxo de processamento das imagens.
- Matriz de autorização.
- Política de armazenamento e acesso.
- Resultados dos testes.
- Evidências HTTP e SSR.
- Pendências e riscos.

A etapa seguinte somente poderá começar após análise e aprovação da DESG-V2-007.