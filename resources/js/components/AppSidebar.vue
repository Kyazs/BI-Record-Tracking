<script setup lang="ts">
import {
    Sidebar,
    SidebarContent,
    SidebarGroup,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    type SidebarProps,
    SidebarRail,
} from '@/components/ui/sidebar';

import { usePage } from '@inertiajs/vue3';
import { FileArchive, Home, LogOut, UserPlus, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const page = usePage();
const currentUrl = computed(() => page.url);

const props = defineProps<SidebarProps>();

// This is sample data.
const data = ref({
    navMain: [
        {
            title: 'Dashboard',
            url: '/dashboard',
            icon: Home,
            isActive: true,
        },
        {
            title: 'Manage User',
            url: '/manage-user',
            icon: Users,
            isActive: false,
        },
        {
            title: 'Manage Applicant',
            url: '/manage-applicant',
            icon: UserPlus,
            isActive: false,
        },
        {
            title: 'Manage Log',
            url: '/logs',
            icon: FileArchive,
            isActive: false,
        },
    ],
});

const navMain = ref([
    { name: 'Dashboard', href: '/dashboard', isActive: false },
    { name: 'Users', href: '/manage-user', isActive: false },
    { name: 'Applicant', href: '/manage-applicant', isActive: false },
]);

const isActive = (href: string) => {
  return currentUrl.value === href; // Exact match only
};

</script>

<template>
    <Sidebar v-bind="props">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <a href="#">
                            <div
                                class="flex aspect-square size-8 items-center justify-center rounded-lg bg-sidebar-primary text-sidebar-primary-foreground"
                            >
                                <FileArchive class="size-4" />
                            </div>
                            <div class="flex flex-col gap-0.5 leading-none">
                                <span class="font-semibold">BI - Record Tracking</span>
                                <span class="">v1.0.0</span>
                            </div>
                        </a>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>
        <SidebarContent>
            <SidebarGroup>
                <SidebarMenu>
                    <SidebarMenuItem v-for="(item, index) in data.navMain" :key="item.title">
                        <SidebarMenuButton as-child :is-active="isActive(item.url)">
                            <a :href="item.url" class="flex items-center gap-2 font-medium">
                                <component :is="item.icon" class="size-4" />
                                {{ item.title }}
                            </a>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                    <SidebarMenuItem>
                        <SidebarMenuButton as-child>
                            <a href="/logout" class="flex items-center gap-2 font-medium text-red-500">
                                <LogOut class="size-4" />
                                Logout
                            </a>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>
        <SidebarRail />
    </Sidebar>
</template>
