import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { router, usePage } from '@inertiajs/react';
import { Building2Icon } from 'lucide-react';
import { useEffect, useState } from 'react';

export default function OrganizationSwitcher() {
    const { organizations = [], currentOrganization, auth } = usePage().props;

    if (!organizations.length) {
        return null;
    }

    /**
     * PANDUAN PENTING (SHADCN UI SELECT):
     * 1. Atribut `value` pada <Select> dan <SelectItem> MESTI berjenis String.
     * 2. Gunakan String(nilai) atau `${nilai}` jika data asal adalah Integer. Jika tidak, UI akan papar nombor sahaja.
     * 3. Pastikan nilai (value) terpilih sememangnya wujud di dalam senarai (array) yang di-map.
     */
    const isSystemOwner = auth.user?.is_admin;
    const value = currentOrganization
        ? String(currentOrganization.id)
        : isSystemOwner
          ? 'all'
          : String(organizations[0]?.id ?? '');

    // 4. Gunakan state tempatan (Optimistic UI) supaya teks label dikemas kini serta-merta
    const [selectedValue, setSelectedValue] = useState(value);
    useEffect(() => {
        setSelectedValue(value);
    }, [value]);

    // Pastikan currentOrganization sentiasa ada dalam senarai pilihan untuk dipaparkan.
    // Jika ia tiada, komponen SelectValue akan gagal mencari label dan terus memaparkan nombor ID.
    const displayOrganizations = [...organizations];
    if (
        currentOrganization &&
        !displayOrganizations.find((org) => String(org.id) === String(currentOrganization.id))
    ) {
        displayOrganizations.unshift(currentOrganization);
    }

    const handleChange = (nextValue) => {
        setSelectedValue(nextValue); // UI dikemas kini serta-merta, elak papar nombor

        if (nextValue === 'all') {
            router.post(
                route('admin.organization.switch'),
                { organization_id: '' },
                { preserveScroll: true },
            );

            return;
        }

        router.post(
            route('admin.organization.switch'),
            { organization_id: nextValue },
            { preserveScroll: true },
        );
    };

    // 5. PENYELESAIAN DEFINITIF: 
    // Cari teks nama organisasi secara manual untuk disumbat terus ke dalam UI.
    // Ini memintas pepijat (bug) Radix UI yang kadang-kala keliru dan memaparkan nombor.
    const selectedLabel = selectedValue === 'all'
        ? 'All organizations'
        : displayOrganizations.find((org) => String(org.id) === selectedValue)?.name;

    return (
        <div className="flex min-w-0 items-center gap-2">
            <Building2Icon className="size-4 shrink-0 text-muted-foreground" />
            <Select value={selectedValue} onValueChange={handleChange}>
                <SelectTrigger className="h-8 w-[min(100%,14rem)] border-dashed">
                    <SelectValue placeholder="Select organization">
                        {selectedLabel}
                    </SelectValue>
                </SelectTrigger>
                <SelectContent align="start">
                    {isSystemOwner && (
                        <SelectItem value="all">All organizations</SelectItem>
                    )}
                    {displayOrganizations.map((organization) => (
                        <SelectItem
                            key={organization.id}
                            value={String(organization.id)}
                        >
                            {organization.name}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
        </div>
    );
}