export type Request<T extends { id: any }> = Omit<T, 'id'>;

export type Resource<T> = {
    data: T;
}

export type Collection<T> = {
    data: T[];
};
