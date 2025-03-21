<script lang="ts">
export const iframeHeight = '800px';
</script>

<script setup lang="ts">
import { AutoForm } from '@/components/ui/auto-form';
import { Button } from '@/components/ui/button';
import { Toaster, useToast } from '@/components/ui/toast';
import AppLayout from '@/layouts/AppLayout.vue';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';
import { z } from 'zod';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Manage User', href: '/manage-user' },
    { title: 'Create User', href: '' },
];

const schema = z.object({
    name: z.string().nonempty('Name is required.'),
    email: z.string().email('Invalid email address.'),
    password: z.string().min(4, 'Password must be at least 4 characters.'),
    role: z.enum(['admin', 'officer']).default('officer'),
});

const toast = useToast();

const onSubmit = async (formData) => {
    try {
        const requestData = new FormData();
        Object.entries(formData).forEach(([key, value]) => {
            requestData.append(key, value);
        });

        const response = await axios.post('/admin/user', requestData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        toast.toast({
            title: 'Success',
            description: response.data.message || 'User created successfully!',
            variant: 'default',
        });

        // Redirect to the manage user page
        setTimeout(() => {
            window.location.href = '/manage-user';
        }, 2000); // Add a slight delay to allow the toast to display
    } catch (error) {
        console.error('❌ Error submitting form:', error.response?.data || error.message);
        toast.toast({
            title: 'Error',
            description: error.response?.data?.message || 'Failed to create user.',
            variant: 'destructive',
        });
    }
};
</script>

<template>
    <Head title="Create User" />
    <Toaster /> <!-- Ensure Toaster is included here -->
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <div class="flex flex-col gap-4 md:flex-row">
                <AutoForm
                    class="m-auto w-2/3 space-y-8 rounded-lg bg-gray-900 p-8"
                    :schema="schema"
                    @submit="onSubmit"
                    :field-config="{
                        name: { label: 'Name', inputProps: { required: true } },
                        email: { label: 'Email', inputProps: { required: true, type: 'email' } },
                        password: { label: 'Password', inputProps: { required: true, type: 'password' } },
                        role: { label: 'Role', component: 'select', inputProps: { required: true } },
                    }"
                >
                    <div class="flex justify-end">
                        <Button type="submit">Submit</Button>
                    </div>
                </AutoForm>
            </div>
        </div>
    </AppLayout>
</template>
