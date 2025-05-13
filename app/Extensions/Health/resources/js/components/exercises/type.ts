export type Data = {
    id: number;
    name: string | null;
    description: string | null;
    has_rep: boolean;
    has_weight: boolean;
    has_distance: boolean;
    distance_unit: string | null,
    has_calorie: boolean;
    has_duration: boolean;
    duration_unit: string | null;
};

export type Resource = {
    data: Data;
}

export type Collection = {
    data: Data[];
};
