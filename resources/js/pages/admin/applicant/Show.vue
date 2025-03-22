<script lang="ts">
export const iframeHeight = '800px';
export const description = 'A sidebar with submenus.';
export default {
    methods: {
        handleImageError(event, key) {
            console.error(`❌ Image load error for: ${key}`);
            event.target.style.display = 'none'; // Hides broken images
        },
        openImage(url) {
            this.selectedImage = url;
            this.showModal = true;
        },
    },
    components: { VueEasyLightbox },
    data() {
        return {
            showModal: false,
            selectedImage: '',
        };
    },
};
</script>

<script setup lang="ts">
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
    AlertDialogTrigger,
} from '@/components/ui/alert-dialog';
import { Button } from '@/components/ui/button';
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select/';
import { toast } from '@/components/ui/toast';
import Toaster from '@/components/ui/toast/Toaster.vue';
import ToastProvider from '@/components/ui/toast/ToastProvider.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head, router, usePage } from '@inertiajs/vue3';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import { ref } from 'vue';
import VueEasyLightbox from 'vue-easy-lightbox';
import * as z from 'zod';

// Props (Received from Inertia Laravel Controller)
const props = defineProps<{
    applicant: {
        id: number;
        full_name: string;
        phone: string;
        date_of_birth: string;
        age: number;
        address: string;
        birth_place: string;
        school: string;
        status: string;
        officer_name: string;
        created_at: string;
    };
    documents: Object[];
}>();

// Access the shared Inertia data
const auth = usePage().props.auth as { user?: { role?: string } };
const documents = ref({ ...props.documents }); // Ensure reactivity
const updatedFiles = ref({}); // Track modified images
const handleFileUpload = (event, key) => {
    const file = event.target.files[0];
    if (!file) return;

    const fileURL = URL.createObjectURL(file);

    // Update UI immediately with new image preview
    documents.value = { ...documents.value, [key]: fileURL };

    // Store actual file for submission
    updatedFiles.value = { ...updatedFiles.value, [key]: file };
};

// Define Form Schema
const formSchema = toTypedSchema(
    z.object({
        full_name: z.string().min(2).max(50),
        phone: z.string().min(10),
        date_of_birth: z.string(),
        age: z.number().min(18),
        address: z.string().min(5),
        birth_place: z.string().min(2),
        school: z.string().min(2),
        status: z.string(),
        officer_name: z.string(),
    }),
);

// Status Colors
const statusColors = {
    Cleared: 'bg-green-600 text-white',
    'Not Cleared': 'bg-red-600 text-white',
    Pending: 'bg-yellow-500 text-black',
};

// Edit Mode State
const isEditing = ref(false);

// Store Applicant Data
const applicant = ref({ ...props.applicant });

// Store Original Data (For Cancel)
const originalData = ref({ ...props.applicant });

// Vee-Validate Form Handling
const { isFieldDirty, handleSubmit, setValues } = useForm({
    validationSchema: formSchema,
    initialValues: applicant.value,
});

// Enable Editing
const startEditing = () => {
    isEditing.value = true;
    originalData.value = { ...applicant.value }; // Save Original Values
};

// Cancel Editing
const cancelEditing = () => {
    isEditing.value = false;
    setValues(originalData.value); // Reset Form to Original Values
    documents.value = { ...props.documents }; // Reset Documents
};

// Save Changes
const saveChanges = handleSubmit(async (values) => {
    console.log('Form Values:', values); // Log the form values for debugging

    const formData = new FormData();

    // Append regular form data
    Object.entries(values).forEach(([key, value]) => {
        formData.append(key, value);
    });

    // Append modified images
    Object.entries(updatedFiles.value).forEach(([key, file]) => {
        formData.append(`file[${key}]`, file);
    });

    try {
        const response = await fetch(`/applicant/${applicant.value.id}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: formData,
        });

        if (!response.ok) throw new Error('Failed to save changes');

        const responseData = await response.json();

        // Update local state with the response data
        applicant.value = responseData.applicant;
        documents.value = responseData.documents;

        updatedFiles.value = {}; // Reset modified files
        isEditing.value = false;

        toast({
            title: 'Saved Successfully!',
            description: 'Your changes have been updated.',
            variant: 'default',
        });

        // Reload the page to fetch the latest data
        window.location.reload();
    } catch (error) {
        toast({
            title: 'Error',
            description: 'Could not save changes.',
            variant: 'destructive',
        });
    }
});


// Delete Applicant
const deleteApplicant = async () => {
    try {
        const response = await fetch(`/applicant/${applicant.value.id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (!response.ok) throw new Error('Failed to delete');

        toast({
            title: 'Deleted Successfully!',
            description: 'The applicant has been removed.',
            variant: 'default',
        });
        setTimeout(() => {
            window.location.href = '/manage-applicant';
        }, 2000);

        // Redirect or update UI after deletion
    } catch (error) {
        toast({
            title: 'Error',
            description: error.message || 'Could not delete applicant.',
            variant: 'destructive',
        });
    }
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manage Applicant',
        href: '/manage-applicant',
    },
    {
        title: `Applicant ID: ${applicant.value.id}`,
        href: `#`,
    },
];
</script>

<template>
    <Head title="Show Applicant" />
    <Toaster />
    <ToastProvider />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="relative min-h-[100vh] flex-1 md:min-h-min">
                <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-semibold text-gray-200">APPLICANT DETAILS</span>
                        <div class="flex gap-2">
                            <Button v-if="!isEditing && auth?.user?.role === 'admin'" class="bg-green-700 text-white hover:bg-green-900" @click="startEditing"> Edit </Button>
                            <AlertDialog v-if="!isEditing && auth?.user?.role === 'admin'">
                                <AlertDialogTrigger> <Button variant="destructive"> Delete </Button></AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                                        <AlertDialogDescription>
                                            This action cannot be undone. This will permanently delete the applicant details.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel @click="cancelEditing">Cancel</AlertDialogCancel>
                                        <AlertDialogAction @click="deleteApplicant">Continue</AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col gap-6">
                        <form class="space-y-6" @submit.prevent="saveChanges">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <FormField v-slot="{ componentField }" name="full_name" :validate-on-blur="!isFieldDirty">
                                    <FormItem>
                                        <FormLabel>Full Name</FormLabel>
                                        <FormControl>
                                            <Input type="text" v-bind="componentField" :disabled="!isEditing" />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>

                                <FormField v-slot="{ componentField }" name="status">
                                    <FormItem>
                                        <FormLabel>Status</FormLabel>
                                        <FormControl>
                                            <Select v-model="applicant.status" :disabled="!isEditing">
                                                <SelectTrigger
                                                    class="w-full rounded-lg border px-4 py-2 text-sm font-medium shadow-sm transition-all focus:ring-2"
                                                    :class="[statusColors[applicant.status] || 'bg-transparent text-white']"
                                                >
                                                    <SelectValue>{{ applicant.status }}</SelectValue>
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="Cleared">Cleared</SelectItem>
                                                    <SelectItem value="Not Cleared">Not Cleared</SelectItem>
                                                    <SelectItem value="Pending">Pending</SelectItem>
                                                </SelectContent>
                                            </Select>
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>

                                <FormField v-slot="{ componentField }" name="phone">
                                    <FormItem>
                                        <FormLabel>Phone Number</FormLabel>
                                        <FormControl>
                                            <Input type="text" v-bind="componentField" :disabled="!isEditing" />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>

                                <FormField v-slot="{ componentField }" name="date_of_birth">
                                    <FormItem>
                                        <FormLabel>Date of Birth</FormLabel>
                                        <FormControl>
                                            <Input type="date" v-bind="componentField" :disabled="!isEditing" />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>

                                <FormField v-slot="{ componentField }" name="age">
                                    <FormItem>
                                        <FormLabel>Age</FormLabel>
                                        <FormControl>
                                            <Input type="number" v-bind="componentField" :disabled="!isEditing" />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>

                                <FormField v-slot="{ componentField }" name="address">
                                    <FormItem>
                                        <FormLabel>Address</FormLabel>
                                        <FormControl>
                                            <Input type="text" v-bind="componentField" :disabled="!isEditing" />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>

                                <FormField v-slot="{ componentField }" name="birth_place">
                                    <FormItem>
                                        <FormLabel>Birth Place</FormLabel>
                                        <FormControl>
                                            <Input type="text" v-bind="componentField" :disabled="!isEditing" />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>

                                <FormField v-slot="{ componentField }" name="school">
                                    <FormItem>
                                        <FormLabel>School</FormLabel>
                                        <FormControl>
                                            <Input type="text" v-bind="componentField" :disabled="!isEditing" />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>

                                <FormField v-slot="{ componentField }" name="officer_name">
                                    <FormItem>
                                        <FormLabel>Officer In-Charged</FormLabel>
                                        <FormControl>
                                            <Input type="text" v-bind="componentField" :disabled="!isEditing" />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>

                                <FormField v-slot="{ componentField }" name="created_at">
                                    <FormItem>
                                        <FormLabel>Created At</FormLabel>
                                        <FormControl>
                                            <Input 
                                                type="text" 
                                                v-bind="componentField" 
                                                :value="new Date(applicant.created_at).toLocaleString()" 
                                                disabled 
                                            />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>
                            </div>
                            <div v-if="!isEditing" class="mt-8">
                                <h2 class="mb-4 text-xl font-semibold text-gray-200">Applicant Documents</h2>
                                <div v-if="documents" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                    <div
                                        v-for="(url, key) in documents"
                                        :key="key"
                                        class="flex flex-col items-center rounded-lg border border-gray-700 bg-gray-800 p-4 shadow-md"
                                    >
                                        <p class="mb-2 text-sm font-medium text-gray-300">
                                            {{ key.replace('_path', '').replace('_', ' ').toUpperCase() }}
                                        </p>
                                        <div class="relative flex h-40 w-full items-center justify-center overflow-hidden rounded-lg bg-gray-700">
                                            <img
                                                v-if="url"
                                                :src="url"
                                                class="h-full w-auto cursor-pointer object-contain"
                                                alt="Document"
                                                @error="handleImageError($event, key)"
                                                @click="openImage(url)"
                                            />
                                            <p v-else class="text-sm text-red-500">No file uploaded</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- Lightbox Component -->
                                <vue-easy-lightbox :visible="showModal" :imgs="[selectedImage]" @hide="showModal = false" />
                            </div>

                            <div v-else class="mt-8">
                                <h2 class="mb-4 text-xl font-semibold text-gray-200">Edit Applicant Documents</h2>
                                <div v-if="documents" class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                    <div
                                        v-for="(url, key) in documents"
                                        :key="key"
                                        class="flex flex-col items-center rounded-lg border border-gray-700 bg-gray-800 p-4 shadow-md"
                                    >
                                        <p class="mb-2 text-sm font-medium text-gray-300">
                                            {{ key.replace('_path', '').replace('_', ' ').toUpperCase() }}
                                        </p>
                                        <div class="relative flex h-40 w-full items-center justify-center overflow-hidden rounded-lg bg-gray-700">
                                            <input
                                                type="file"
                                                class="block w-full text-sm text-gray-300 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-700 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-gray-200 hover:file:bg-gray-600"
                                                @change="handleFileUpload($event, key)"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
                        <!-- Action Buttons -->
                        <div v-if="isEditing" class="flex justify-end gap-2">
                            <Button type="button" variant="secondary" @click="cancelEditing">Cancel</Button>
                            <AlertDialog>
                                <AlertDialogTrigger> <Button variant="outline"> Save Changes </Button></AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                                        <AlertDialogDescription>
                                            This action cannot be undone. This will update the applicant details.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel @click="cancelEditing">Cancel</AlertDialogCancel>
                                        <AlertDialogAction @click="saveChanges">Continue</AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
