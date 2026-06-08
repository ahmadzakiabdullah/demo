import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { router, usePage } from '@inertiajs/react';
import { Building2Icon } from 'lucide-react';

export default function OrganizationSwitcher() {
    const { organizations = [], currentOrganization, auth } = usePage().props;

    if (!organizations.length) {
        return null;
    }

    const isSystemOwner = auth.user?.is_admin;
    const value = currentOrganization
        ? String(currentOrganization.id)
        : isSystemOwner
          ? 'all'
          : String(organizations[0]?.id ?? '');

    const handleChange = (nextValue) => {
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

    return (
        <div className="flex min-w-0 items-center gap-2">
            <Building2Icon className="size-4 shrink-0 text-muted-foreground" />
            <Select value={value} onValueChange={handleChange}>
                <SelectTrigger className="h-8 w-[min(100%,14rem)] border-dashed">
                    <SelectValue placeholder="Select organization" />
                </SelectTrigger>
                <SelectContent align="start">
                    {isSystemOwner && (
                        <SelectItem value="all">All organizations</SelectItem>
                    )}
                    {organizations.map((organization) => (
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