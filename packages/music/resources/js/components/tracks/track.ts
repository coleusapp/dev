import { OptionCollection } from '@/types';
import { Collection, Request, Resource } from '@coleus/support/types/resource';
import { type InjectionKey } from 'vue';

export type TrackData = {
    id: string;
    title: string;
    artist_id: string | number;
    artist?: string;
    album_id: string | number | null;
    album?: string | null;
    genre_id: string | number | null;
    genre?: string | null;
    duration: number | null;
    track_number: number | null;
    path?: string | null;
};

export type TrackResource = Resource<TrackData>;
export type TrackRequest = Request<TrackData>;
export type TrackCollection = Collection<TrackData>;

export const resourceKey = Symbol() as InjectionKey<TrackResource>;
export const artistsKey = Symbol() as InjectionKey<OptionCollection>;
export const albumsKey = Symbol() as InjectionKey<OptionCollection>;
export const genresKey = Symbol() as InjectionKey<OptionCollection>;
