export type Data = {
    id: string;
    name: string;
    description: string;
    has_rep: boolean;
    has_weight: boolean;
    has_distance: boolean;
    distance_unit?: string,
    has_calorie: boolean;
    has_duration: boolean;
    duration_unit?: string;
};

export type Resource = {
    data: Data;
}

export type Collection = {
    data: Data[];
};

export type Request = {
    name: string;
};
