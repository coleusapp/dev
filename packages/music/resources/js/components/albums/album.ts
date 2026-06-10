import { OptionCollection } from '@/types';
import { Collection, Request, Resource } from '@coleus/support/types/resource';
import { type InjectionKey } from 'vue';

export type AlbumData = {
    id: string;
    title: string;
    artist_id: string | number;
    artist?: string;
    release_date: string | null;
};

export type AlbumResource = Resource<AlbumData>;
export type AlbumRequest = Request<AlbumData>;
export type AlbumCollection = Collection<AlbumData>;

export const resourceKey = Symbol() as InjectionKey<AlbumResource>;
export const artistsKey = Symbol() as InjectionKey<OptionCollection>;
