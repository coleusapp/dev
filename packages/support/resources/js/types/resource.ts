export type Request<T extends { id: any }> = Omit<T, 'id'>;

export type Resource<T> = {
    data: T;
}

export type Meta = {
    current_page: number;
    from: number;
    to: number;
    last_page: number;
    per_page: number;
    total: number;
    path: string;
    links: {url: string | null, label: string | null, active: boolean}[];
}

export type Collection<T> = {
    data: T[];
    meta: Meta
};
