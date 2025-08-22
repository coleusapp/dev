<template>
    <UiApp>
        <div class="bg-gray-100 flex min-h-screen flex-1 flex-col justify-center px-6 py-12 lg:px-8">
            <div class="sm:mx-auto sm:w-full sm:max-w-sm">
                <h2 class="mt-10 text-center text-2xl/9 font-bold tracking-tight text-gray-900">
                    <slot name="header" />
                </h2>
            </div>

            <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
                <slot />
            </div>
        </div>
    </UiApp>
</template>
