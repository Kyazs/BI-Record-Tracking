<script lang="ts">
export const iframeHeight = '800px';
export const description = 'A sidebar with submenus.';
</script>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCaption, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table/';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { LucideArrowLeft, LucideArrowRight, LucideEye, PlusCircle } from 'lucide-vue-next';
import { defineProps } from 'vue';

const props = defineProps({
    users: Object,
});

// Handle pagination
const goToPage = (url: string) => {
    if (url) {
        router.get(url); // Fetch the next page using Inertia
    }
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/',
    },
    {
        title: 'Manage User',
        href: '/manage-user',
    },
];
</script>

<template>
    <Head title="Manage User" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="relative min-h-[100vh] flex-1 md:min-h-min">
                <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="flex items-center justify-between">
                        <span className="text-lg font-semibold text-gray-200">LIST OF USERS</span>
                        <Button @click="router.get('/create-user')">
                            <PlusCircle class="h-6 w-6 text-black" />
                            Add
                        </Button>
                    </div>
                    <Table class="">
                        <TableCaption>
                            <div>
                                <div class="flex items-center justify-center gap-4">
                                    <button
                                        :disabled="!users.prev_page_url"
                                        @click="goToPage(users.prev_page_url)"
                                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                                    >
                                        <LucideArrowLeft class="h-4 w-4 text-white" />
                                    </button>
                                    <span>Page {{ users.current_page }} of {{ users.last_page }}</span>
                                    <button
                                        :disabled="!users.next_page_url"
                                        @click="goToPage(users.next_page_url)"
                                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                                    >
                                        <LucideArrowRight class="h-4 w-4 text-white" />
                                    </button>
                                </div>
                                A list of registered account on the system.
                            </div>
                        </TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead>ID</TableHead>
                                <TableHead>Full Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Role</TableHead>
                                <TableHead>Created At</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="user in users.data" :key="user.id">
                                <TableCell class="font-medium">{{ user.id }}</TableCell>
                                <TableCell>{{ user.name }}</TableCell>
                                <TableCell>{{ user.email }}</TableCell>
                                <TableCell>{{ user.role }}</TableCell>
                                <TableCell :value="new Date(user.created_at).toLocaleString()">{{ new Date(user.created_at).toLocaleString() }}</TableCell>
                                <TableCell>
                                    <LucideEye
                                        class="h-6 w-6 cursor-pointer text-gray-700 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2"
                                        @click="router.get(`/user/${user.id}`)"
                                    />
                                </TableCell>
                            </TableRow>
                        </TableBody>
                        <TableFooter class="self-center bg-transparent"> </TableFooter>
                    </Table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
