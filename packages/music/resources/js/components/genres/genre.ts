import { Collection, Request, Resource } from '@coleus/support/types/resource';
import type { InjectionKey } from 'vue';

export type GenreData = {
    id: string;
    name: string;
};

export type GenreResource = Resource<GenreData>;
export type GenreRequest = Request<GenreData>;
export type GenreCollection = Collection<GenreData>;

export const resourceKey = Symbol() as InjectionKey<GenreResource>;
