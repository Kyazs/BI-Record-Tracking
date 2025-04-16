<script lang="ts">
export const iframeHeight = '800px';
export const description = 'A sidebar with submenus.';
</script>

<script setup lang="ts">
import { Badge } from '@/components/ui/badge/';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import Input from '@/components/ui/input/Input.vue';
import { Table, TableBody, TableCaption, TableCell, TableFooter, TableHead, TableHeader, TableRow } from '@/components/ui/table/';
import { Toaster, useToast } from '@/components/ui/toast';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { debounce } from 'lodash'; // ✅ Debounce to prevent excessive API calls
import { LucideArrowLeft, LucideArrowRight, LucideEye, LucidePlusCircle } from 'lucide-vue-next';
import { onMounted, onUnmounted, reactive, ref, watch } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Manage Applicant', href: '/manage-applicant' }];

const props = defineProps<{
    applicants: {
        data: Array<any>;
        current_page: number;
        last_page: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    newApplicantId: number;
}>();

const { toast } = useToast();
const applicants = reactive({
    data: props.applicants.data,
    current_page: props.applicants.current_page,
    last_page: props.applicants.last_page,
    prev_page_url: props.applicants.prev_page_url,
    next_page_url: props.applicants.next_page_url,
});
const lastApplicantId = ref(props.newApplicantId || 0);
const search = ref('');
const selectedStatus = ref(''); // Default to empty (no filter)
const sortOrder = ref('desc'); // Default sorting: Descending
let eventSource: EventSource | null = null;

// Handle pagination
const goToPage = async (url: string | null) => {
    if (url) {
        try {
            const response = await fetch(url);
            const data = await response.json();
            applicants.data = data.data;
            applicants.current_page = data.current_page;
            applicants.last_page = data.last_page;
            applicants.next_page_url = data.next_page_url;
            applicants.prev_page_url = data.prev_page_url;
        } catch (error) {
            console.error('Error fetching paginated data:', error);
        }
    }
};

// 🔥 Debounced API Call to Reduce Lag
const fetchApplicants = debounce(async () => {
    let queryParams = new URLSearchParams();

    if (search.value) {
        queryParams.append('search', search.value);
    }

    if (selectedStatus.value) {
        queryParams.append('status', selectedStatus.value);
    }

    queryParams.append('sort', sortOrder.value);

    try {
        const response = await fetch(`/applicants?${queryParams.toString()}`);
        const data = await response.json();

        // Update your reactive applicants object
        applicants.data = data.data;
        applicants.current_page = data.current_page;
        applicants.last_page = data.last_page;
        applicants.next_page_url = data.next_page_url;
        applicants.prev_page_url = data.prev_page_url;
    } catch (error) {
        console.error('Error fetching applicants:', error);
    }
}, 300);

watch([search, selectedStatus, sortOrder], fetchApplicants);

// ✅ Fetch Initial Data & Setup SSE
onMounted(() => {
    fetchApplicants();
    // eventSource = new EventSource('/stream-applicants');
    eventSource.onmessage = (event) => {
        try {
            const newData = JSON.parse(event.data);
            applicants.data = newData.applicants.data; // Update the data in applicants object
            applicants.current_page = newData.applicants.current_page;
            applicants.last_page = newData.applicants.last_page;
            // applicants.prev_page_url = newData.applicants.prev_page_url;
            // applicants.next_page_url = newData.applicants.next_page_url;

            if (newData.newApplicant && newData.newApplicant.id > lastApplicantId.value) {
                lastApplicantId.value = newData.newApplicant.id;
                toast({
                    title: 'New Applicant Added',
                    description: `${newData.newApplicant.full_name} has just been added.`,
                });
            }
        } catch (error) {
            console.error('Error processing SSE data:', error);
        }
    };
    eventSource.onerror = (error) => {
        console.error('SSE Error:', error);
        eventSource?.close();
    };
});

// ✅ Cleanup SSE on Unmount
onUnmounted(() => {
    eventSource?.close();
});
</script>

<template>
    <Head title="Manage Applicant" />
    <Toaster />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 md:flex-row">
                <Input type="text" v-model="search" placeholder="Search applicants..." class="w-1/2"></Input>
                <div class="space-x-4">
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline">
                                Filter by:
                                {{ selectedStatus || 'All Status' }}
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="w-fit">
                            <DropdownMenuLabel>Filter By</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuRadioGroup v-model="selectedStatus">
                                <DropdownMenuRadioItem value="" default>All Status</DropdownMenuRadioItem>
                                <DropdownMenuRadioItem value="Pending">Pending</DropdownMenuRadioItem>
                                <DropdownMenuRadioItem value="Cleared">Cleared</DropdownMenuRadioItem>
                                <DropdownMenuRadioItem value="Not Cleared">Not Cleared</DropdownMenuRadioItem>
                            </DropdownMenuRadioGroup>
                        </DropdownMenuContent>
                    </DropdownMenu>
                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button variant="outline">{{ sortOrder === 'asc' ? 'Oldest' : 'Latest' }}</Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent class="w-56">
                            <DropdownMenuLabel>Sort By</DropdownMenuLabel>
                            <DropdownMenuSeparator />
                            <DropdownMenuRadioGroup v-model="sortOrder">
                                <DropdownMenuRadioItem value="asc">Oldest</DropdownMenuRadioItem>
                                <DropdownMenuRadioItem value="desc">Latest</DropdownMenuRadioItem>
                            </DropdownMenuRadioGroup>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
                <div class="ml-auto">
                    <Button @click="router.get('/manage-applicant/create-applicant')">
                        <LucidePlusCircle class="h-6 w-6" />
                        Add
                    </Button>
                </div>
            </div>
            <div class="relative min-h-[100vh] flex-1 md:min-h-min">
                <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <span className="text-lg font-semibold text-gray-200">Lists of Applicant</span>
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
                                A list of applicants in your database.
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
