import { BreadcrumbItem, Table } from '@/types';
import { InertiaForm } from '@inertiajs/vue3';

export type Category = {
    name?: string;
};

export type Form = InertiaForm<Category>;

export interface CategoryResource {
    data: {
        id: number;
        name: string;
    };
}

interface CategoryTable extends Table {
    records: {
        data: CategoryResource[];
    }
}
