export class IconColumnProps {
    private value: any = null;
    private column: {
        icons: {[key: string]: string};
        defaultIcon: string,
        colors: {[key: string]: string};
        defaultColor: string,
    } = {
        icons: {
            'true': 'i-lucide-check',
            'false': 'i-lucide-x'
        },
        defaultIcon: 'i-lucide-x',
        colors: {
            'true': 'text-green-500',
            'false': 'text-red-500'
        },
        defaultColor: 'text-gray-500',
    }

    make(value: any) {
        this.value = value;

        return this;
    }

    icon(icons: any, defaultIcon: any = this.column.defaultIcon) {
        this.column.icons = icons;
        this.column.defaultIcon = defaultIcon;

        return this;
    }

    color(colors: any, defaultColor: any = this.column.defaultColor) {
        this.column.colors = colors;
        this.column.defaultColor = defaultColor;

        return this;
    }

    build() {
        return {
            name: this.column.icons[this.value] || this.column.defaultIcon,
            class: this.column.colors[this.value] || this.column.defaultColor,
        }
    }
}