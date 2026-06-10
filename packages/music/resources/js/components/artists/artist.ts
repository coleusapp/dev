import { Collection, Request, Resource } from '@coleus/support/types/resource';
import type { InjectionKey } from 'vue';

export type ArtistData = {
    id: string;
    name: string;
    bio: string | null;
};

export type ArtistResource = Resource<ArtistData>;
export type ArtistRequest = Request<ArtistData>;
export type ArtistCollection = Collection<ArtistData>;

export const resourceKey = Symbol() as InjectionKey<ArtistResource>;
