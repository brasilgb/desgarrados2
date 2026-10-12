# Proposta técnica — áudio cultural

Status: proposta para decisão; não implementada e sem player/reprodução pública nesta rodada.

## Escopo e separação de direitos

Um item musical deve representar duas obras relacionadas, mas juridicamente distintas:

- **Composição/obra**: título, compositores/autores, letrista quando aplicável, editora/titular, situação autoral, fundamento e referência da licença/autorização, território, prazo e restrições.
- **Fonograma/gravação**: intérpretes, músicos, produtor/fonogramista, titular do master, selo, ISRC quando existir, estúdio/origem e autorização específica para disponibilizar aquele arquivo.

Autorização da composição não prova autorização do fonograma. Não permitir publicação quando qualquer camada necessária estiver não comprovada. Guardar comprovantes no mesmo serviço privado versionado de direitos, com auditoria e retirada imediata.

## Reuso de infraestrutura e dados

Reutilizar o disco privado, hashing, quarentena, limite de upload, processamento assíncrono e auditoria do serviço de mídia. Não ampliar o fluxo atual de imagens sem modelar MIME/codecs, transcodificação segura, duração e custos. Proposta: `audio_assets` imutáveis para os arquivos/master e `revision_audio` para o vínculo e ordem por revisão, mantendo metadados editoriais junto à revisão. Saídas derivadas em codecs aprovados devem ser servidas com MIME correto, range requests e cache controlado; originais nunca ficam públicos.

## Player e acessibilidade

- Controles nativos `<audio controls>` antes de considerar um player customizado; play/pause, seek, volume e estado devem ser operáveis por teclado e leitor de tela.
- Sem reprodução automática; foco visível, nome acessível e estado de carregamento/erro; não depender só de cor ou ícone.
- Mostrar título, intérpretes, compositores/titulares, duração e licença/créditos; distinguir explicitamente obra e gravação.
- Disponibilizar transcrição revisada da fala/canto quando aplicável e descrição de sons relevantes; oferecer letra somente se houver direito próprio para exibi-la.
- Respeitar preferências de movimento/dados e impedir embeds externos sem política de privacidade e consentimento.

## Segurança, ciclo editorial e decisões pendentes

Áudio deve pertencer à revisão, copiar vínculos ao criar uma nova versão e congelar versões em revisão/aprovadas. Aprovação/publicação deve verificar autorização da obra e do fonograma, processamento concluído e ausência de bloqueio/revogação. Auditoria deve cobrir upload, alteração de metadados/direitos, publicação e retirada.

Antes de implementar, decidir: formatos/codecs e limite de duração/tamanho; política de retenção dos masters e comprovantes; quem pode ouvir prévias privadas; licença padrão do conteúdo; requisitos de transcrição; custos de banda/armazenamento; e versão mínima de MySQL/MariaDB. Criar testes de range requests, vazamento privado, revogação, teclado, axe-core e leitores de tela. Nenhuma publicação musical deve ser habilitada por padrão.
