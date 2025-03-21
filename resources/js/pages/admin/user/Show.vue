<script lang="ts">
export const iframeHeight = '800px';
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
import { toast } from '@/components/ui/toast';
import Toaster from '@/components/ui/toast/Toaster.vue';
import ToastProvider from '@/components/ui/toast/ToastProvider.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { toTypedSchema } from '@vee-validate/zod';
import { useForm } from 'vee-validate';
import { ref, reactive } from 'vue';
import * as z from 'zod';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

// Props (Received from Inertia Laravel Controller)
const props = defineProps<{
    user: {
        id: number;
        name: string;
        email: string;
        role: string;
        created_at: string;
    };
}>();

// Breadcrumbs
const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Manage User', href: '/manage-user' },
    { title: `User ID: ${props.user.id}`, href: '#' },
];

// Edit Mode State
const isEditing = ref(false);

// Make the user object reactive
const user = reactive({ ...props.user });

// Store Original Data (For Cancel)
const originalData = ref({ ...props.user });

// Define Form Schema
const formSchema = toTypedSchema(
    z.object({
        name: z.string().min(2).max(50),
        email: z.string().email(),
        role: z.string().min(4),
    }),
);

// Vee-Validate Form Handling
const { isFieldDirty, handleSubmit, setValues } = useForm({
    validationSchema: formSchema,
    initialValues: user,
});

// Enable Editing
const startEditing = () => {
    isEditing.value = true;
    originalData.value = { ...user }; // Save Original Values
};

// Cancel Editing
const cancelEditing = () => {
    isEditing.value = false;
    setValues(originalData.value); // Reset Form to Original Values
};

// Save Changes
const saveChanges = handleSubmit(async (values) => {
    try {
        const response = await fetch(`/user/${user.id}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
            body: JSON.stringify({ ...values, role: user.role }),
        });

        if (!response.ok) throw new Error('Failed to save changes');

        const responseData = await response.json();

        isEditing.value = false;

        toast({
            title: 'Saved Successfully!',
            description: 'Your changes have been updated.',
            variant: 'default',
        });

        setTimeout(() => {
            window.location.href = '/manage-user';
        }, 2000);
    } catch (error) {
        toast({
            title: 'Error',
            description: 'Could not save changes.',
            variant: 'destructive',
        });
    }
});

// Delete User
const deleteUser = async () => {
    try {
        const response = await fetch(`/user/${user.id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            },
        });

        if (!response.ok) throw new Error('Failed to delete user');

        toast({
            title: 'Deleted Successfully!',
            description: 'The user has been removed.',
            variant: 'default',
        });
        setTimeout(() => {
            window.location.href = '/manage-user';
        }, 2000);
    } catch (error) {
        toast({
            title: 'Error',
            description: error.message || 'Could not delete user.',
            variant: 'destructive',
        });
    }
};
</script>

<template>
    <Head title="User Details" />
    <Toaster />
    <ToastProvider />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="relative min-h-[100vh] flex-1 md:min-h-min">
                <div class="rounded-xl border border-sidebar-border/70 p-4 dark:border-sidebar-border">
                    <div class="flex items-center justify-between">
                        <span class="text-lg font-semibold text-gray-200">USER DETAILS</span>
                        <div class="flex gap-2">
                            <Button v-if="!isEditing" class="bg-green-700 text-white hover:bg-green-900" @click="startEditing"> Edit </Button>
                            <AlertDialog v-if="!isEditing">
                                <AlertDialogTrigger> <Button variant="destructive"> Delete </Button></AlertDialogTrigger>
                                <AlertDialogContent>
                                    <AlertDialogHeader>
                                        <AlertDialogTitle>Are you absolutely sure?</AlertDialogTitle>
                                        <AlertDialogDescription>
                                            This action cannot be undone. This will permanently delete the user.
                                        </AlertDialogDescription>
                                    </AlertDialogHeader>
                                    <AlertDialogFooter>
                                        <AlertDialogCancel @click="cancelEditing">Cancel</AlertDialogCancel>
                                        <AlertDialogAction @click="deleteUser">Continue</AlertDialogAction>
                                    </AlertDialogFooter>
                                </AlertDialogContent>
                            </AlertDialog>
                        </div>
                    </div>

                    <div class="mt-4 flex flex-col gap-6">
                        <form class="space-y-6" @submit.prevent="saveChanges">
                            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                                <FormField v-slot="{ componentField }" name="name" :validate-on-blur="!isFieldDirty">
                                    <FormItem>
                                        <FormLabel>Name</FormLabel>
                                        <FormControl>
                                            <Input type="text" v-bind="componentField" :disabled="!isEditing" />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>

                                <FormField v-slot="{ componentField }" name="email">
                                    <FormItem>
                                        <FormLabel>Email</FormLabel>
                                        <FormControl>
                                            <Input type="email" v-bind="componentField" :disabled="!isEditing" />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>

                                <FormField v-slot="{ componentField }" name="role">
                                    <FormItem>
                                        <FormLabel>Role</FormLabel>
                                        <FormControl>
                                            <Select v-model="user.role" :disabled="!isEditing">
                                                <SelectTrigger class="w-full rounded-lg border px-4 py-2 text-sm font-medium shadow-sm transition-all focus:ring-2">
                                                    <SelectValue>{{ user.role }}</SelectValue>
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="admin">Admin</SelectItem>
                                                    <SelectItem value="officer">Officer</SelectItem>
                                                </SelectContent>
                                            </Select>
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
                                                :value="new Date(user.created_at).toLocaleString()" 
                                                disabled 
                                            />
                                        </FormControl>
                                        <FormMessage />
                                    </FormItem>
                                </FormField>
                            </div>
                        </form>

                        <div v-if="isEditing" class="flex justify-end gap-2">
                            <Button type="button" variant="secondary" @click="cancelEditing">Cancel</Button>
                            <Button type="submit" variant="outline" @click="saveChanges">Save Changes</Button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>


