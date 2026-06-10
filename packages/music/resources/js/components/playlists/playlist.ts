import { OptionCollection } from '@/types';
import { Collection, Request, Resource } from '@coleus/support/types/resource';
import type { InjectionKey } from 'vue';

export type PlaylistTrack = {
    playlist_id: number | null;
    track_id: number | null;
};

export type PlaylistData = {
    id: string;
    name: string;
    description: string | null;
    tracks?: PlaylistTrack[];
};

export type PlaylistResource = Resource<PlaylistData>;
export type PlaylistRequest = Request<PlaylistData>;
export type PlaylistCollection = Collection<PlaylistData>;

export const resourceKey = Symbol() as InjectionKey<PlaylistResource>;
export const tracksKey = Symbol() as InjectionKey<OptionCollection>;
