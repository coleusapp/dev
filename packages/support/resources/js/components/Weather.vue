<script setup lang="ts">
import { Cloud, CloudFog, CloudHail, CloudLightning, CloudRain, CloudSun, Snowflake, Sun } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

interface WeatherData {
    temperature: number;
    high: number;
    low: number;
    condition: string;
}

const weatherData = ref<WeatherData | null>(null);

const weatherMappings = {
    Clear: {
        tailwindClass: 'bg-blue-100 text-blue-800',
        icon: Sun,
    },
    'Mainly clear': {
        tailwindClass: 'bg-blue-200 text-blue-900',
        icon: CloudSun,
    },
    'Partly cloudy': {
        tailwindClass: 'bg-gray-200 text-gray-800',
        icon: Cloud,
    },
    Cloudy: {
        tailwindClass: 'bg-gray-500 text-white',
        icon: Cloud,
    },
    Overcast: {
        tailwindClass: 'bg-gray-400 text-gray-900',
        icon: Cloud,
    },
    Fog: {
        tailwindClass: 'bg-gray-500 text-gray-100',
        icon: CloudFog,
    },
    Snow: {
        tailwindClass: 'bg-blue-200 text-blue-700',
        icon: Snowflake,
    },
    Rain: {
        tailwindClass: 'bg-blue-300 text-blue-800',
        icon: CloudRain,
    },
    Thunderstorms: {
        tailwindClass: 'bg-yellow-300 text-yellow-800',
        icon: CloudLightning,
    },
    Hail: {
        tailwindClass: 'bg-indigo-300 text-indigo-800',
        icon: CloudHail,
    },
};

onMounted(async () => {
    navigator.geolocation.getCurrentPosition(async (pos) => {
        const { latitude, longitude } = pos.coords;
        const res = await fetch(
            `https://api.open-meteo.com/v1/forecast?latitude=${latitude}&longitude=${longitude}&current_weather=true&daily=temperature_2m_max,temperature_2m_min,weathercode&timezone=auto`,
        );
        const data = await res.json();

        const weatherConditions: { [key: string]: string } = {
            '0': 'Clear',
            '1': 'Mainly clear',
            '2': 'Partly cloudy',
            '3': 'Cloudy',
            '4': 'Overcast',
            '5': 'Fog',
            '6': 'Snow',
            '7': 'Rain',
            '8': 'Thunderstorms',
            '9': 'Hail',
        };

        const condition = weatherConditions[data.current_weather.weathercode] || 'Unknown';

        weatherData.value = {
            temperature: data.current_weather.temperature,
            high: data.daily.temperature_2m_max[0], // Today's max temperature
            low: data.daily.temperature_2m_min[0], // Today's min temperature
            condition,
        };
    });
});
</script>

<template>
    <template v-if="weatherData">
        <div class="w-full rounded-3xl shadow-md p-4 h-[180px]" :class="weatherMappings[weatherData.condition]?.tailwindClass || ''">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col">
                    <span class="font-extrabold">Salt Lake City</span>
                    <span class="text-5xl font-light">{{ weatherData.temperature }}°</span>
                </div>
                <div class="flex flex-col -space-y-1">
                    <component :is="weatherMappings[weatherData.condition]?.icon" />
                    <span class="text-sm font-bold">{{ weatherData.condition }}</span>
                    <span class="text-sm font-bold">H:{{ weatherData.high }}° L:{{ weatherData.low }}°</span>
                </div>
            </div>
        </div>
    </template>
</template>
