<script lang="ts">
export const iframeHeight = '800px';
export const description = 'A sidebar with submenus.';
</script>

<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuRadioGroup, DropdownMenuRadioItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Toaster, useToast } from '@/components/ui/toast';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { toTypedSchema } from '@vee-validate/zod';
import axios from 'axios';
import { differenceInYears } from 'date-fns';
import { useForm } from 'vee-validate';
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
const fileErrors = ref(Object.keys(fileLabels.value).reduce((acc, key) => ({ ...acc, [key]: '' }), {}));

const toast = useToast();

const validateAndSetFile = (event, fieldName) => {
    const file = event.target.files?.[0];
    fileErrors.value[fieldName] = ''; // Reset error for the field

    if (!file) {
        fileErrors.value[fieldName] = 'No file selected. Please choose a file.';
        return;
    }

    const errors = [
        !ACCEPTED_FILE_TYPES.includes(file.type) && `Invalid file type: ${file.type}. Only JPG, PNG, and PDF allowed.`,
        file.size > MAX_FILE_SIZE && 'File must be less than 5MB.',
    ].filter(Boolean);

    if (errors.length) {
        fileErrors.value[fieldName] = errors[0];
        event.target.value = ''; // Reset input
        return;
    }

    selectedFiles.value[fieldName] = file;
};

const formSchema = toTypedSchema(
    z
        .object({
            first_name: z.string().min(2).max(50),
            middle_name: z.string().min(2).max(50),
            last_name: z.string().min(2).max(50),
            suffix: z.string().optional().default(''),
            street: z.string().min(2).max(50),
            barangay: z.string().min(2).max(50),
            city: z.string().min(2).max(50),
            province: z.string().min(2).max(50),
            age: z.coerce.number().min(18),
            phone: z.string().regex(/^09\d{9}$/),
            date_of_birth: z.string().refine((dob) => new Date(dob) < new Date(), { message: 'Birth date cannot be in the future.' }),
            birth_place: z.string().min(2).max(50),
            school: z.string().min(2).max(50),
            status: z.enum(['Cleared', 'Pending', 'Not Cleared']).default('Pending'),
            pnp_officer_name: z.string().min(2).max(50),
            school_officer_name: z.string().min(2).max(50),
            barangay_officer_name: z.string().min(2).max(50),
            rtc_officer_name: z.string().min(2).max(50),
        })
        .refine((data) => differenceInYears(new Date(), new Date(data.date_of_birth)) === data.age, {
            message: 'Age does not match the birth date.',
            path: ['age'],
        }),
);

// Map formSchema fields to schemaFields for dynamic rendering

const form = useForm({
    validationSchema: formSchema,
    initialValues: {
        status: 'Pending',
    },
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

const onSubmit = async () => {
    try {
        const isValid = await form.validate(); // Validate the form values
        if (!isValid) {
            toast.toast({
                title: 'Validation Error',
                description: 'Please fix the errors in the form.',
                variant: 'destructive',
            });
            return;
        }

        // Ensure form.values is updated with the correct data
        const formData = form.values;

        const requestData = new FormData();
        
        // Explicitly add each officer name field to ensure they're included
        requestData.append('first_name', formData.first_name);
        requestData.append('middle_name', formData.middle_name);
        requestData.append('last_name', formData.last_name);
        requestData.append('suffix', formData.suffix || '');
        requestData.append('age', formData.age);
        requestData.append('date_of_birth', formData.date_of_birth);
        requestData.append('street', formData.street);
        requestData.append('barangay', formData.barangay);
        requestData.append('city', formData.city);
        requestData.append('province', formData.province);
        requestData.append('phone', formData.phone);
        requestData.append('birth_place', formData.birth_place);
        requestData.append('school', formData.school);
        requestData.append('status', formData.status);
        
        // Ensure officer names are explicitly included
        requestData.append('pnp_officer_name', formData.pnp_officer_name);
        requestData.append('barangay_officer_name', formData.barangay_officer_name);
        requestData.append('school_officer_name', formData.school_officer_name);
        requestData.append('rtc_officer_name', formData.rtc_officer_name);
        
        // Add files
        Object.entries(selectedFiles.value).forEach(([key, file]) => {
            if (file) requestData.append(`file[${key}]`, file);
        });


        const response = await axios.post('/manage-applicant/create-applicant', requestData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        toast.toast({ title: 'Success', description: 'Application submitted successfully!', variant: 'default' });
        setTimeout(() => (window.location.href = '/manage-applicant'), 2000);
    } catch (error) {
        console.error('Error submitting form:', error);
        console.error('Error response:', error.response?.data);
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
            <form class="m-auto w-2/3 space-y-8 rounded-lg bg-gray-900 p-8" @submit.prevent="onSubmit">
                <!-- Name Fields in One Row -->
                <div class="flex flex-wrap gap-4 border-b border-gray-600 pb-4">
                    <FormField name="first_name" v-slot="{ componentField }">
                        <FormItem class="flex-1">
                            <FormLabel>First Name</FormLabel>
                            <FormControl>
                                <Input v-bind="componentField" type="text" v-model="form.values.first_name" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                    <FormField name="middle_name" v-slot="{ componentField }">
                        <FormItem class="flex-1">
                            <FormLabel>Middle Name</FormLabel>
                            <FormControl>
                                <Input v-bind="componentField" type="text" v-model="form.values.middle_name" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                    <FormField name="last_name" v-slot="{ componentField }">
                        <FormItem class="flex-1">
                            <FormLabel>Last Name</FormLabel>
                            <FormControl>
                                <Input v-bind="componentField" type="text" v-model="form.values.last_name" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                    <FormField name="suffix" v-slot="{ componentField }">
                        <FormItem class="w-24">
                            <FormLabel>Suffix</FormLabel>
                            <FormControl>
                                <Input v-bind="componentField" type="text" v-model="form.values.suffix" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                </div>
                <div class="flex flex-wrap gap-4 border-b border-gray-600 pb-4">
                    <FormField name="street" v-slot="{ componentField }">
                        <FormItem class="flex-1">
                            <FormLabel>Street</FormLabel>
                            <FormControl>
                                <Input v-bind="componentField" type="text" v-model="form.values.street" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                    <FormField name="barangay" v-slot="{ componentField }">
                        <FormItem class="flex-1">
                            <FormLabel>Barangay</FormLabel>
                            <FormControl>
                                <Input v-bind="componentField" type="text" v-model="form.values.barangay" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                    <FormField name="city" v-slot="{ componentField }">
                        <FormItem class="flex-1">
                            <FormLabel>City</FormLabel>
                            <FormControl>
                                <Input v-bind="componentField" type="text" v-model="form.values.city" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                    <FormField name="province" v-slot="{ componentField }">
                        <FormItem class="flex-1">
                            <FormLabel>Province</FormLabel>
                            <FormControl>
                                <Input v-bind="componentField" type="text" v-model="form.values.province" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                </div>
                <div class="flex flex-wrap gap-4 border-b border-gray-600 pb-4">
                    <FormField name="phone" v-slot="{ componentField }">
                        <FormItem class="flex-1">
                            <FormLabel>Phone</FormLabel>
                            <FormControl>
                                <Input v-bind="componentField" type="text" placeholder="09xxxxxxxxx" v-model="form.values.phone" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                    <FormField name="age" v-slot="{ componentField }">
                        <FormItem class="flex-1">
                            <FormLabel>Age</FormLabel>
                            <FormControl>
                                <Input v-bind="componentField" type="number" v-model="form.values.age" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                    <FormField name="date_of_birth" v-slot="{ componentField }">
                        <FormItem class="flex-1">
                            <FormLabel>Date of Birth</FormLabel>
                            <FormControl>
                                <Input v-bind="componentField" type="date" v-model="form.values.date_of_birth" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                </div>
                <FormField name="birth_place" v-slot="{ componentField }">
                    <FormItem class="flex-1">
                        <FormLabel>Birth Place</FormLabel>
                        <FormControl>
                            <Input v-bind="componentField" type="text" v-model="form.values.birth_place" />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>
                <FormField name="school" v-slot="{ componentField }">
                    <FormItem>
                        <FormLabel>School</FormLabel>
                        <FormControl>
                            <Input v-bind="componentField" type="text" v-model="form.values.school" />
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>
                <FormField name="status" v-slot="{ componentField }">
                    <FormItem>
                        <FormLabel>Status</FormLabel>
                        <br />
                        <FormControl>
                            <select
                                v-model="form.values.status"
                                class="form-select block w-full rounded-lg border border-gray-600 bg-gray-800 text-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-500 focus:ring-opacity-50"
                            >
                                <option value="Pending">Pending</option>
                                <option value="Cleared">Cleared</option>
                                <option value="Not Cleared">Not Cleared</option>
                            </select>
                        </FormControl>
                        <FormMessage />
                    </FormItem>
                </FormField>
                <div class="flex flex-wrap gap-4 border-b border-gray-600 pb-4">
                    <FormField name="pnp_officer_name" v-slot="{ componentField }">
                        <FormItem class="flex-1">
                            <FormLabel>PNP Officer Name</FormLabel>
                            <FormControl>
                                <Input v-bind="componentField" type="text" v-model="form.values.pnp_officer_name" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                    <FormField name="school_officer_name" v-slot="{ componentField }">
                        <FormItem class="flex-1">
                            <FormLabel>School Officer Name</FormLabel>
                            <FormControl>
                                <Input v-bind="componentField" type="text" v-model="form.values.school_officer_name" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                    <FormField name="barangay_officer_name" v-slot="{ componentField }">
                        <FormItem class="flex-1">
                            <FormLabel>Barangay Officer Name</FormLabel>
                            <FormControl>
                                <Input v-bind="componentField" type="text" v-model="form.values.barangay_officer_name" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                    <FormField name="rtc_officer_name" v-slot="{ componentField }">
                        <FormItem class="flex-1">
                            <FormLabel>RTC Officer Name</FormLabel>
                            <FormControl>
                                <Input v-bind="componentField" type="text" v-model="form.values.rtc_officer_name" />
                            </FormControl>
                            <FormMessage />
                        </FormItem>
                    </FormField>
                </div>

                <div class="flex flex-col gap-4 border-b border-gray-600 pb-4">
                    <div v-for="(label, key) in fileLabels" :key="key" class="flex flex-col gap-2">
                        <div class="flex items-center gap-4">
                            <label :for="key" class="w-1/3 text-right font-medium text-gray-300">{{ label }}</label>
                            <div class="flex-1">
                                <input
                                    type="file"
                                    :id="key"
                                    accept=".jpg, .jpeg, .png, .pdf"
                                    @change="validateAndSetFile($event, key)"
                                    class="block w-full rounded-lg border border-gray-600 bg-gray-800 text-gray-300 file:mr-4 file:rounded-lg file:border-0 file:bg-gray-700 file:px-4 file:py-2 file:text-gray-300 hover:file:bg-gray-600"
                                />
                            </div>
                        </div>
                        <p v-if="fileErrors[key]" class="text-sm text-red-500">{{ fileErrors[key] }}</p>
                    </div>
                </div>

                <div class="flex justify-end">
                    <Button type="submit">Submit</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
