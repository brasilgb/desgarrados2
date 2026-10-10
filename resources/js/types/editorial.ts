export type Option = { id: number; name: string };
export type Page<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
};
export type Source = {
    title: string;
    url: string;
    attribution: string;
    accessed_at: string;
};
export type Content = {
    title: string;
    summary: string;
    body: string;
    public_byline: string;
    sources: Source[];
};
export type MediaImage = {
    src: string;
    width: number;
    height: number;
    srcset: string;
};
export type MediaUsage = {
    purpose: 'cover' | 'content';
    alt: string;
    caption: string | null;
    credit: string;
    image: MediaImage | null;
};
export type MediaAsset = {
    uuid: string;
    name: string | null;
    width: number;
    height: number;
    processing: 'pending' | 'processing' | 'ready' | 'failed';
    status: 'active' | 'blocked';
    blocked_reason: string | null;
    rights_type: string | null;
    rights_holder: string | null;
    license: string | null;
    rights_notes: string | null;
    has_rights: boolean;
    preview: MediaImage | null;
    can: { update: boolean; block: boolean; delete: boolean };
};
export type RevisionMediaItem = MediaUsage & { id: number; asset: MediaAsset };
export type PublicStory = {
    slug: string;
    type: string;
    title: string;
    summary: string | null;
    byline: string | null;
    published_at: string;
    body?: string;
    sources?: Source[];
    cover: MediaUsage | null;
    images?: MediaUsage[];
    municipality: (Option & { slug: string }) | null;
    category: { name: string; slug: string } | null;
    tags: { name: string; slug: string }[];
    regions: (Option & { slug: string })[];
};
export const labels: Record<string, string> = {
    article: 'Artigo',
    news: 'Notícia',
    chronicle: 'Crônica',
    causo: 'Causo',
    cultural_history: 'História cultural',
    personal_story: 'História pessoal',
    migration_story: 'História de migração',
    memory: 'Memória',
    draft: 'Rascunho',
    in_review: 'Em revisão',
    approved: 'Aprovada',
    rejected: 'Rejeitada',
    scheduled: 'Agendada',
    published: 'Publicada',
    hidden: 'Oculta',
    archived: 'Arquivada',
    historias: 'Histórias',
    causos: 'Causos',
    'cultura-e-tradicoes': 'Cultura e tradições',
    own: 'Autoria própria',
    authorized: 'Uso autorizado pelo titular',
    licensed: 'Licenciada',
    creative_commons: 'Creative Commons',
    public_domain: 'Domínio público',
    pending: 'Aguardando processamento',
    processing: 'Processando',
    ready: 'Pronta',
    failed: 'Falha no processamento',
    blocked: 'Bloqueada',
};
