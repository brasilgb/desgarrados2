export type TerritoryState = {
    id: number;
    name: string;
    slug: string;
    abbreviation: string;
    municipalities_count?: number;
};
export type Municipality = {
    id: number;
    name: string;
    slug: string;
    ibge_code: string;
};
export type Region = {
    id: number;
    name: string;
    slug: string;
    kind: 'cultural' | 'geographic';
    description: string | null;
};
export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
};
