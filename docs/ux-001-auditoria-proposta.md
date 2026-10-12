# DESG-V2-UX-001 — Auditoria e proposta visual

## Auditoria da interface existente

Escopo observado: `PublicLayout`, home (`welcome.tsx`), região (`territory/region.tsx`), município, estado, feed e publicação, `StoryCard`/`StoryFigure`, navegação pública e estilos globais.

### O que aproveitar

- Laravel 13 + React/Inertia já entrega páginas renderizadas no servidor; home, publicação e região já definem canonical, descrição e Open Graph.
- `PublicLayout` oferece skip link, identidade persistente, links principais e rodapé. Há tema claro/escuro e várias ações já têm foco visível.
- `StoryCard` e `StoryFigure` já expressam uma hierarquia editorial reaproveitável e respeitam a entrega de mídia autorizada. As páginas usam conteúdo real do backend e estados vazios editoriais, sem fixtures.
- Home, estado, município, feed, publicação e região já formam uma estrutura de descoberta territorial/editorial. Paginação e links de navegação territorial existem.
- A home e a região usam grids responsivos; publicação prioriza coluna legível. O modelo já prevê dados opcionais, importantes para estados vazios.

### Oportunidades e riscos de UX

- Identidade visual pública é aplicada por cores Tailwind inline em várias páginas; `app.css` tem tokens globais neutros, não uma escala semântica regional compartilhada. Pequenos ajustes podem divergir entre páginas.
- Cabeçalho público usa `flex-wrap` em mobile. É funcional, mas pode ocupar várias linhas; falta uma navegação compacta e uma entrada de busca pública global.
- A descoberta depende de seções e links; não há affordance de busca pública no cabeçalho. Busca/filtros existem em contexto editorial, mas não formam uma jornada pública unificada.
- Cards em grade são visualmente uniformes; falta variar densidade e ritmo editorial entre destaque, lista cronológica e descoberta de lugares.
- Página regional apresenta contexto, destaques, histórias e municípios, mas não oferece um índice de seção/atalhos; quando não há destaque, a seção some sem explicar esse estado.
- Foco explícito, contraste de texto secundário e alvo de toque precisam de validação sistemática em componentes públicos; algumas ações têm foco visível, outras dependem do comportamento padrão do navegador.
- A troca entre descoberta editorial e territorial pode ficar mais clara com rótulos, breadcrumbs e ações consistentes, mantendo as rotas e a autorização atuais.

## Direção visual proposta: Regional Contemporâneo

Um arquivo editorial vivo, e não um portal de notícias: tipografia com voz, respiro de página impressa, fotografia documental tratada com respeito e orientação territorial clara. A referência é a sensação de exploração e contexto de acervos; não se reproduzem layouts, marcas ou componentes das referências do briefing.

| Token proposto | Claro     | Escuro    | Uso                             |
| -------------- | --------- | --------- | ------------------------------- |
| `terra-paper`  | `#F4F0E6` | `#17241F` | Fundo principal                 |
| `mate`         | `#263F35` | `#E5E9DD` | Texto principal e ação primária |
| `leather`      | `#8C513B` | `#D49A78` | Acento e links editoriais       |
| `wine`         | `#6D3542` | `#D89AA5` | Marcadores de coleção/destaque  |
| `ink-muted`    | `#5E665F` | `#B9C2B8` | Metadados, sujeito a contraste  |
| `rule`         | `#D5CDBD` | `#3A4941` | Divisórias e contornos          |

Paleta é uma hipótese de protótipo, não um padrão aprovado. Validar contrastes WCAG AA antes de promover tokens a produção.

### Tipografia, ritmo e componentes

- Título/display: serif editorial de boa leitura; corpo: sans humanista já instalada (Instrument Sans), com fallback local. Confirmar licenciamento e carregamento antes de adicionar família externa.
- Escala inicial: corpo 16–18 px, legenda 14 px, H1 fluido `clamp(2.6rem, 6vw, 5.5rem)`, H2 32–40 px; largura de leitura 65–72 caracteres.
- Espaçamento em passos de 4 px; conteúdo máximo 1200 px; margens fluidas 20–48 px; seções com separação generosa, sem cartões encaixotados em excesso.
- Componentes sugeridos: `PublicMasthead` (desktop/mobile), busca compacta, `StoryCard` (lead/standard/list), `PlaceCard`, breadcrumb territorial, seção com âncora, chip de região, empty state, pagination. Preferir HTML semântico e componentes atuais.
- Fotografia: proporção consistente por função, corte não destrutivo, crédito/legenda legíveis; sem sobreposição de texto sobre áreas imprevisíveis da imagem. Não criar imagens/publicações fictícias como conteúdo real.
- Claro/escuro devem compartilhar tokens semânticos, não inverter cores manualmente em cada página. Foco com contorno de alto contraste; alvos de toque de pelo menos 44 × 44 px; respeitar redução de movimento.

## Protótipo navegável

Abra [`prototypes/ux-001-publico.html`](prototypes/ux-001-publico.html) no navegador. A navegação alterna por âncora entre home e página regional; redimensione a janela para ver o arranjo móvel e use o controle de tema. O artefato é estático, sem API/backend e sem conteúdo de produção: todo texto editorial mostrado é marcado como conteúdo demonstrativo. Foi mantido fora das rotas da aplicação para não alterar contratos nem antecipar uma reformulação ampla antes da aprovação.

### Home — estrutura proposta

1. Masthead compacto: marca, História, Territórios, busca; menu mobile acessível.
2. Abertura editorial curta com frase-manifesto e busca “Que lugar ou história você procura?”.
3. Lead editorial assimétrico, com imagem/caption e metadados discretos; abaixo, dois caminhos de leitura.
4. Faixa de descoberta territorial com regiões como lugares, não cards genéricos.
5. Trilhas “Histórias”, “Causos” e “Cultura” com ritmo visual próprio e link para ver tudo.
6. Convite final para explorar município/estado.

### Região — estrutura proposta

1. Breadcrumb `Brasil / Regiões / [nome]`, tipo de região e título.
2. Apresentação curta; ação para explorar municípios relacionados.
3. Destaque principal e trilha cronológica, com sumário/atalhos de seção em telas largas.
4. Municípios em lista escaneável com UF, sem mapa decorativo que sugira precisão inexistente.
5. Estados vazios explícitos e honestos; conteúdo real somente quando elegível/publicado.

## Plano incremental

1. Aprovar direção, protótipos, paleta e prioridade mobile com produto/editorial.
2. Validar acessibilidade (contraste, teclado, foco, zoom, leitores de tela) e layout em larguras 360, 768 e 1280 px.
3. Implementar tokens e masthead público isoladamente; preservar Inertia, SSR, SEO, links, regras de mídia e visibilidade.
4. Evoluir home e página regional reutilizando `StoryCard`/`StoryFigure`; validar estados vazios e conteúdo sem imagem.
5. Aplicar o padrão aprovado a publicação, feed e busca; harmonização administrativa em etapa separada.
6. Executar testes automatizados, inspeção visual desktop/mobile, SSR e revisão de performance antes de qualquer publicação.

## Evidências e testes desta etapa

- Auditoria estática dos arquivos React/TSX, rotas e estilos listados; inventário de componentes e jornadas acima.
- Protótipo isolado, responsivo por CSS e navegável por âncoras; possui rótulo persistente “Protótipo — conteúdo demonstrativo”.
- Nenhuma migration, endpoint, regra editorial, política de mídia ou página de produção foi alterada nesta etapa.
- Validação automatizada de compilação ainda precisa ocorrer; protótipo HTML estático não entra no pipeline JS. Inspeção visual automatizada depende de navegador headless disponível no host.
