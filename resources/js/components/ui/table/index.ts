export { default as Table } from './Table.vue';
export { default as ActionLink } from './ActionLink.vue';

export interface IColumn {
    label: string;
    value: string;
    sort?: [{label: string, value: string}[]];
}

export interface ILinks {
    first?: string;
    last?: string;
    prev?: string;
    next?: string;
}

export interface IMeta {
    current_page: number;
    from: number;
    last_page: number;
    links: {
        url?: string;
        label?: string;
        active: boolean;
    }[];
    path: string;
    per_page: number;
    to: number;
    total: number;
}

export interface IRecords {
    data: unknown[];
    links: ILinks;
    meta: IMeta;
}

export interface ITable {
    records: IRecords;
    columns: string[];
    headers: string[];
}
