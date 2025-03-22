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
import { Button } from '@/components/ui/button';

import { usePage } from '@inertiajs/vue3';
import { FileArchive, Home, LogOut, UserPlus, Users } from 'lucide-vue-next';
import { computed, ref, onMounted } from 'vue';

const page = usePage();
const currentUrl = computed(() => page.url);

const props = defineProps<SidebarProps>();

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
            title: 'Logs',
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

const isAdmin = computed(() => page.props.auth.user.role === 'admin'); // Assuming role is available in page props

const filteredNavMain = computed(() => {
    return data.value.navMain.filter(item => {
        if (item.url === '/manage-user' && !isAdmin.value) {
            return false; // Exclude "Manage User" for non-admin users
        }
        return true;
    });
});

const successMessage = ref('');
const errorMessage = ref('');
const isBackingUp = ref(false);

const handleBackup = () => {
    isBackingUp.value = true;

    fetch(route('backup.run'))
        .then(response => response.json())
        .then(() => {
            checkBackupStatus();
        })
        .catch(error => {
            console.error("Backup failed:", error);
            errorMessage.value = "Backup failed. Please try again.";
            isBackingUp.value = false;
        });
};

const checkBackupStatus = () => {
    fetch(route('backup.status'))
        .then(response => response.json())
        .then(data => {
            if (data.status === "in_progress") {
                successMessage.value = "Backup is in progress...";
                setTimeout(checkBackupStatus, 3000); // Check every 3 seconds
            } else if (data.status === "completed") {
                successMessage.value = "Backup Completed!";
                isBackingUp.value = false;
            } else {
                successMessage.value = "No backup in progress.";
                isBackingUp.value = false;
            }
        })
        .catch(error => {
            console.error("Error checking backup status:", error);
            errorMessage.value = "Error checking backup status.";
            isBackingUp.value = false;
        });
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
                    <SidebarMenuItem v-for="(item, index) in filteredNavMain" :key="item.title">
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
        <div class="mt-auto p-4">
            <!-- Alert Messages -->
            <div v-if="successMessage" class="alert alert-success">
                {{ successMessage }}
            </div>
            <div v-if="errorMessage" class="alert alert-danger">
                {{ errorMessage }}
            </div>

            <!-- Backup Button -->
            <Button variant="destructive" class="btn btn-primary w-full" :disabled="isBackingUp" @click="handleBackup" id="backup-btn"> 
                {{ isBackingUp ? 'Backing Up...' : 'Backup Now' }}
            </Button>
        </div>
    </Sidebar>
</template>
