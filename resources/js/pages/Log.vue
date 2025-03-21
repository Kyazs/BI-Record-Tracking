<script setup lang="ts">
import { Accordion, AccordionContent, AccordionItem, AccordionTrigger } from '@/components/ui/accordion';
import { Table, TableBody, TableCaption, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Logs',
        href: '/logs',
    },
];
const props = defineProps({
    logs: Object,
});

const logs = ref(props.logs.data); // Use the paginated data from the backend

const parseJson = (jsonString: string) => {
    try {
        return JSON.parse(jsonString);
    } catch (error) {
        return null; // Return null if JSON parsing fails
    }
};
</script>

<template>
    <Head title="Logs" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <div class="relative min-h-[100vh] flex-1 rounded-xl border border-sidebar-border/70 dark:border-sidebar-border md:min-h-min">
                <Table>
                    <TableCaption>A list of your recent invoices.</TableCaption>
                    <TableHeader>
                        <TableRow>
                            <TableHead>ID </TableHead>
                            <TableHead class="w-[100px]">Changed by</TableHead>
                            <TableHead>Applicant ID</TableHead>
                            <TableHead>Old Data</TableHead>
                            <TableHead>New Data</TableHead>
                            <TableHead class="w-[100px]">Action</TableHead>
                            <TableHead class="text-right"> Created at </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-for="log in logs" :key="log.id">
                            <TableCell class="font-medium">
                                {{ log.id }}
                            </TableCell>
                            <TableCell>{{ log.user_id || 'N/A' }}</TableCell>
                            <TableCell>{{ log.applicant_id }}</TableCell>
                            <TableCell>
                                <Accordion type="single" collapsible>
                                    <AccordionItem value="item-1">
                                        <AccordionTrigger>View Old Data</AccordionTrigger>
                                        <AccordionContent>
                                            <div v-if="parseJson(log.old_data)" class="max-w-[300px] break-words">
                                                <ul>
                                                    <li v-for="(value, key) in parseJson(log.old_data)" :key="key">
                                                        <strong>{{ key }}:</strong> {{ value }}
                                                    </li>
                                                </ul>
                                            </div>
                                            <div v-else>
                                                Invalid JSON data
                                            </div>
                                        </AccordionContent>
                                    </AccordionItem>
                                </Accordion>
                            </TableCell>
                            <TableCell>
                                <Accordion type="single" collapsible>
                                    <AccordionItem value="item-1">
                                        <AccordionTrigger>View New Data</AccordionTrigger>
                                        <AccordionContent>
                                            <div v-if="parseJson(log.new_data)" class="max-w-[300px] break-words">
                                                <ul>
                                                    <li v-for="(value, key) in parseJson(log.new_data)" :key="key">
                                                        <strong>{{ key }}:</strong> {{ value }}
                                                    </li>
                                                </ul>
                                            </div>
                                            <div v-else>
                                                Invalid JSON data
                                            </div>
                                        </AccordionContent>
                                    </AccordionItem>
                                </Accordion>
                            </TableCell>
                            <TableCell>{{ log.action }}</TableCell>
                            <TableCell class="text-right">
                                {{ new Date(log.created_at).toLocaleString() }}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        </div>
    </AppLayout>
</template>
