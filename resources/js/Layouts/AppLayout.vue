<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();

const flashMessage = computed(() => page.props.flash?.success || page.props.flash?.error || null);
const flashType = computed(() => (page.props.flash?.error ? 'error' : 'success'));

function logout() {
    router.post('/logout');
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-cyan-50">
        <div class="mx-auto flex min-h-screen max-w-[1400px]">
            <aside class="hidden w-72 border-r border-slate-200/70 bg-white/90 p-6 backdrop-blur lg:block">
                <div class="mb-8">
                    <p class="text-xs font-semibold uppercase tracking-[0.22em] text-slate-400">Yandex Reviews</p>
                    <p class="mt-2 text-xl font-bold text-slate-900">Monitor v1.1</p>
                </div>

                <nav class="space-y-2 text-sm">
                    <Link href="/dashboard" class="block rounded-xl px-4 py-2.5 font-medium transition"
                          :class="$page.url.startsWith('/dashboard') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100'">
                        Dashboard
                    </Link>
                    <Link href="/places" class="block rounded-xl px-4 py-2.5 font-medium transition"
                          :class="$page.url.startsWith('/places') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100'">
                        Organizations
                    </Link>
                    <Link href="/reviews" class="block rounded-xl px-4 py-2.5 font-medium transition"
                          :class="$page.url.startsWith('/reviews') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100'">
                        Reviews
                    </Link>
                    <Link href="/settings" class="block rounded-xl px-4 py-2.5 font-medium transition"
                          :class="$page.url.startsWith('/settings') ? 'bg-slate-900 text-white' : 'text-slate-700 hover:bg-slate-100'">
                        Settings
                    </Link>
                </nav>
            </aside>

            <div class="flex min-h-screen flex-1 flex-col">
                <header class="border-b border-slate-200/70 bg-white/85 px-5 py-4 backdrop-blur sm:px-8">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <p class="text-sm text-slate-500">Welcome back</p>
                            <p class="text-base font-semibold text-slate-900">{{ $page.props.auth.user.name }}</p>
                        </div>
                        <button
                            type="button"
                            @click="logout"
                            class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-100"
                        >
                            Logout
                        </button>
                    </div>
                    <div v-if="flashMessage" class="mt-4 rounded-xl px-4 py-3 text-sm font-medium"
                         :class="flashType === 'error' ? 'bg-rose-100 text-rose-700' : 'bg-emerald-100 text-emerald-700'">
                        {{ flashMessage }}
                    </div>
                </header>

                <main class="flex-1 px-5 py-6 sm:px-8 sm:py-8">
                    <slot />
                </main>
            </div>
        </div>
    </div>
</template>
