<script lang="ts">
export const iframeHeight = '800px';
export const description = 'A sidebar with submenus.';
</script>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge/';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCaption, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table/';
import { Toaster, useToast } from '@/components/ui/toast'; // ✅ Import Toast
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { LucideArrowLeft, LucideArrowRight, LucideEye, LucideUser } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '#',
    },
];

const props = defineProps({
    applicants: Object,
    totalApplicants: Number,
    totalUsers: Number,
    newApplicantId: Number,
    error: String, // Add error prop
});
const { toast } = useToast(); // ✅ Initialize Toast
const applicants = ref(props.applicants || { data: [], current_page: 1, last_page: 1, prev_page_url: null, next_page_url: null });
const totalApplicants = ref(props.totalApplicants);
const lastApplicantId = ref(props.newApplicantId || 0); // Track the last known applicant ID

// Handle pagination
const goToPage = (url: string) => {
    if (url) {
        router.get(url);
    }
};

// 🔥 Setup Server-Sent Events (SSE) for real-time updates
onMounted(() => {
    applicants.value = props.applicants;
    totalApplicants.value = props.totalApplicants;

    // ✅ Handle error message
    if (props.error) {
        toast({
            title: 'Error',
            description: props.error,
            variant: 'destructive', // Optional: Use a variant for error styling
        });
    }

    const eventSource = new EventSource('/stream-applicants');

    eventSource.onmessage = (event) => {
        const newData = JSON.parse(event.data);

        // ✅ Preserve pagination while updating the data
        applicants.value = {
            ...newData.applicants,
            data: newData.applicants.data,
        };
        totalApplicants.value = newData.totalApplicants;

        // 🆕 Ensure newApplicant exists and is truly new
        if (newData.newApplicant && newData.newApplicant.id > lastApplicantId.value) {
            lastApplicantId.value = newData.newApplicant.id; // Update last ID
            toast({
                title: 'New Applicant Added',
                description: `${newData.newApplicant.full_name} has just been added.`,
            });
        }
    };

    eventSource.onerror = (error) => {
        console.error('SSE Error:', error);
        eventSource.close();
    };

    onUnmounted(() => {
        eventSource.close();
    });
});
</script>
<template>
    <Head title="Dashboard" />
    <Toaster />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative overflow-hidden">
                    <Card class="h-fit">
                        <CardHeader>
                            <CardTitle> <span class="text-lg font-semibold text-gray-700">Total Applicant</span></CardTitle>
                        </CardHeader>
                        <CardContent>
                            <span class="text-2xl font-bold text-white">
                                {{ totalApplicants ?? 'Loading...' }}
                            </span>
                        </CardContent>
                    </Card>
                </div>
                <div class="relative overflow-hidden">
                    <Card class="h-fit">
                        <CardHeader>
                            <CardTitle>
                                <div class="flex items-center gap-2">
                                    <LucideUser class="h-6 w-6 text-gray-700" />
                                    <span class="text-lg font-semibold text-gray-700">Total Users</span>
                                </div>
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <span class="text-2xl font-bold text-white">{{ totalUsers ?? 'Loading...' }}</span>
                        </CardContent>
                    </Card>
                </div>
            </div>
            <div class="relative min-h-[100vh] flex-1 md:min-h-min">
                <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <span className="text-lg font-semibold text-gray-200">Recent Added Application</span>
                    <Table class="">
                        <TableCaption>
                            <div>
                                <div class="flex items-center justify-center gap-4">
                                    <button
                                        :disabled="!applicants.prev_page_url"
                                        @click="goToPage(applicants.prev_page_url)"
                                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                                    >
                                        <LucideArrowLeft class="h-4 w-4 text-white" />
                                    </button>
                                    <span>Page {{ applicants.current_page }} of {{ applicants.last_page }}</span>
                                    <button
                                        :disabled="!applicants.next_page_url"
                                        @click="goToPage(applicants.next_page_url)"
                                        class="rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 disabled:opacity-50"
                                    >
                                        <LucideArrowRight class="h-4 w-4 text-white" />
                                    </button>
                                </div>
                                A list of your recently added Applicant.
                            </div>
                        </TableCaption>
                        <TableHeader>
                            <TableRow>
                                <TableHead>ID</TableHead>
                                <TableHead>Full Name</TableHead>
                                <TableHead>Age</TableHead>
                                <TableHead>Date of Birth</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Created at</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="applicant in applicants.data" :key="applicant.id">
                                <TableCell class="font-medium">{{ applicant.id }}</TableCell>
                                <TableCell>{{ applicant.full_name }}</TableCell>
                                <TableCell>{{ applicant.age }}</TableCell>
                                <TableCell>{{ applicant.date_of_birth }}</TableCell>
                                <TableCell>
                                    <Badge v-if="applicant.status === 'Cleared'" variant="outline" class="bg-green-700">
                                        {{ applicant.status }}
                                    </Badge>
                                    <Badge v-else-if="applicant.status === 'Pending'" variant="outline" class="bg-yellow-700">
                                        {{ applicant.status }}
                                    </Badge>
                                    <Badge v-else-if="applicant.status === 'Not Cleared'" variant="outline" class="bg-red-700">
                                        {{ applicant.status }}
                                    </Badge>
                                    <Badge v-else variant="default">
                                        {{ applicant.status }}
                                    </Badge>
                                </TableCell>
                                <TableCell>{{ applicant.created_at }}</TableCell>
                                <TableCell>
                                    <LucideEye
                                        class="h-6 w-6 cursor-pointer text-gray-700 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground focus-visible:ring-2"
                                        @click="router.get(`/applicant/${applicant.id}`)"
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
