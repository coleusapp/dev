import { Table } from '@/types';
import { InertiaForm } from '@inertiajs/vue3';

export type Weight = {
    name?: string;
};

export type Form = InertiaForm<Weight>;

export interface WeightResource {
    data: {
        id: number;
        name: string;
    };
}

interface WeightTable extends Table {
    records: {
        data: WeightResource[];
    }
}
