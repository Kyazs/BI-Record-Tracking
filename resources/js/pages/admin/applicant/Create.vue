<script lang="ts">
export const iframeHeight = '800px';
export const description = 'A sidebar with submenus.';
</script>

<script setup lang="ts">
import { AutoForm } from '@/components/ui/auto-form';
import { Button } from '@/components/ui/button';
import { Toaster, useToast } from '@/components/ui/toast';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { differenceInYears } from 'date-fns';
import { ref } from 'vue';
import { z } from 'zod';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Manage Applicant', href: '/manage-applicant' },
    { title: 'Add Applicant', href: '' },
];

const MAX_FILE_SIZE = 5 * 1024 * 1024; // 5MB
const ACCEPTED_FILE_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];

const fileLabels = ref({
    barangay_cert: 'Barangay Certificate',
    pnp_clearance: 'PNP Clearance',
    rtc_clearance: 'RTC Clearance',
    school_cert: 'School Certificate',
    derogatory_records: 'Derogatory Records (Optional)',
});

const selectedFiles = ref(Object.keys(fileLabels.value).reduce((acc, key) => ({ ...acc, [key]: null }), {}));

const toast = useToast();

const validateAndSetFile = (event, fieldName) => {
    const file = event.target.files?.[0];
    if (!file) return;

    const errors = [
        !ACCEPTED_FILE_TYPES.includes(file.type) && `Invalid file type: ${file.type}. Only JPG, PNG, and PDF allowed.`,
        file.size > MAX_FILE_SIZE && 'File must be less than 5MB.',
    ].filter(Boolean);

    if (errors.length) {
        toast.toast({ title: 'Error', description: errors[0], variant: 'destructive' });
        event.target.value = ''; // Reset input
        return;
    }

    selectedFiles.value[fieldName] = file;
};

const schema = z
    .object({
        full_name: z.object({
            first_name: z.string().min(2).max(50),
            middle_name: z.string().min(2).max(50),
            last_name: z.string().min(2).max(50),
            suffix: z.string().optional().default(''),
        }),
        age: z.coerce.number().min(18),
        address: z.object({
            street: z.string().min(2).max(50),
            barangay: z.string().min(2).max(50),
            city: z.string().min(2).max(50),
            province: z.string().min(2).max(50),
        }),
        phone: z.string().regex(/^09\d{9}$/),
        date_of_birth: z.string().refine((dob) => new Date(dob) < new Date(), { message: 'Birth date cannot be in the future.' }),
        birth_place: z.string().min(2).max(50),
        school: z.string().min(2).max(50),
        status: z.enum(['Cleared', 'Pending', 'Not Cleared']).default('Pending'),
        officer_name: z.string().min(2).max(50),
    })
    .refine((data) => differenceInYears(new Date(), new Date(data.date_of_birth)) === data.age, {
        message: 'Age does not match the birth date.',
        path: ['age'],
    });

const appendNestedFields = (data, formData, parentKey = '') => {
    Object.entries(data).forEach(([key, value]) => {
        const fieldKey = parentKey ? `${parentKey}[${key}]` : key;
        if (typeof value === 'object' && value !== null && !(value instanceof File)) {
            appendNestedFields(value, formData, fieldKey);
        } else {
            formData.append(fieldKey, value);
        }
    });
};

const onSubmit = async (formData) => {
    try {
        const requestData = new FormData();
        appendNestedFields(formData, requestData);

        Object.entries(selectedFiles.value).forEach(([key, file]) => {
            if (file) requestData.append(`file[${key}]`, file);
        });

        const response = await axios.post('/manage-applicant/create-applicant', requestData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        toast.toast({ title: 'Success', description: 'Application submitted successfully!', variant: 'default' });
        setTimeout(() => (window.location.href = '/manage-applicant'), 2000);
    } catch (error) {
        toast.toast({
            title: 'Error',
            description: error.response?.data?.message || 'Error submitting form. Please check your inputs.',
            variant: 'destructive',
        });
    }
};
</script>

<template>
    <Head title="Add Applicant" />
    <Toaster />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-4 p-4">
            <AutoForm
                class="m-auto w-2/3 space-y-8 rounded-lg bg-gray-900 p-8"
                :schema="schema"
                :field-config="{
                    full_name: {
                        first_name: { label: 'First Name' },
                        middle_name: { label: 'Middle Name' },
                        last_name: { label: 'Last Name' },
                        suffix: { label: 'Suffix' },
                    },
                    address: {
                        street: { label: 'Street' },
                        barangay: { label: 'Barangay' },
                        city: { label: 'City' },
                        province: { label: 'Province' },
                    },
                    phone: { label: 'Phone', inputProps: { placeholder: '09xxxxxxxxx' } },
                    age: { label: 'Age', inputProps: { type: 'number' } },
                    date_of_birth: { label: 'Birth Date', inputProps: { type: 'date' } },
                    birth_place: { label: 'Birth Place' },
                    school: { label: 'School' },
                    status: { label: 'Status' },
                    officer_name: { label: 'Officer Name' },
                }"
                @submit="onSubmit"
            >
                <div v-for="(label, key) in fileLabels" :key="key">
                    <label :for="key">{{ label }}</label>
                    <input type="file" :id="key" accept=".jpg, .jpeg, .png, .pdf" @change="validateAndSetFile($event, key)" />
                </div>
                <div class="flex justify-end">
                    <Button type="submit">Submit</Button>
                </div>
            </AutoForm>
        </div>
    </AppLayout>
</template>
