import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

function StatusBadge({ status }) {
    const variant =
        status === 'active'
            ? 'default'
            : status === 'published'
              ? 'secondary'
              : status === 'draft'
                ? 'outline'
                : 'secondary';

    return <Badge variant={variant}>{status}</Badge>;
}

export default function Index({ events, filters, statuses, organizations, editionYears }) {
    const { flash } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [organizationId, setOrganizationId] = useState(
        filters.organization_id || '',
    );
    const [editionYear, setEditionYear] = useState(filters.edition_year || '');

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(
                route('admin.events.index'),
                {
                    search,
                    status,
                    organization_id: organizationId,
                    edition_year: editionYear,
                },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, status, organizationId, editionYear]);

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <h2 className="text-xl font-semibold leading-tight text-foreground">
                        Events
                    </h2>
                    <Button render={<Link href={route('admin.events.create')} />}>
                        Create Event
                    </Button>
                </div>
            }
        >
            <Head title="Events" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            {flash.success}
                        </div>
                    )}

                    <Card>
                        <CardHeader>
                            <CardTitle>Manage Events</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex flex-col gap-4 lg:flex-row">
                                <Input
                                    placeholder="Search by name, slug, or location..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="lg:max-w-sm"
                                />
                                <Select
                                    value={status || 'all'}
                                    onValueChange={(value) =>
                                        setStatus(value === 'all' ? '' : value)
                                    }
                                >
                                    <SelectTrigger className="w-full lg:w-40">
                                        <SelectValue placeholder="All statuses" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All statuses</SelectItem>
                                        {statuses.map((item) => (
                                            <SelectItem key={item} value={item}>
                                                {item}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {organizations.length > 1 && (
                                    <Select
                                        value={organizationId || 'all'}
                                        onValueChange={(value) =>
                                            setOrganizationId(
                                                value === 'all' ? '' : value,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="w-full lg:w-56">
                                            <SelectValue placeholder="All organizations" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">
                                                All organizations
                                            </SelectItem>
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
                                )}
                                {editionYears.length > 0 && (
                                    <Select
                                        value={editionYear || 'all'}
                                        onValueChange={(value) =>
                                            setEditionYear(value === 'all' ? '' : value)
                                        }
                                    >
                                        <SelectTrigger className="w-full lg:w-32">
                                            <SelectValue placeholder="All years" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">All years</SelectItem>
                                            {editionYears.map((year) => (
                                                <SelectItem
                                                    key={year}
                                                    value={String(year)}
                                                >
                                                    {year}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                )}
                            </div>

                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Event</TableHead>
                                        <TableHead>Year</TableHead>
                                        <TableHead>Organization</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Dates</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {events.data.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={7}
                                                className="text-center text-muted-foreground"
                                            >
                                                No events found.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        events.data.map((event) => (
                                            <TableRow key={event.id}>
                                                <TableCell>
                                                    <div className="font-medium">
                                                        {event.name}
                                                    </div>
                                                    <div className="text-xs text-muted-foreground">
                                                        {event.slug}
                                                    </div>
                                                </TableCell>
                                                <TableCell>{event.edition_year}</TableCell>
                                                <TableCell>
                                                    {event.organization?.name}
                                                </TableCell>
                                                <TableCell>
                                                    {event.event_type?.name}
                                                </TableCell>
                                                <TableCell>
                                                    <StatusBadge status={event.status} />
                                                </TableCell>
                                                <TableCell className="text-sm text-muted-foreground">
                                                    {event.starts_at ?? '—'}
                                                    {event.ends_at &&
                                                        ` → ${event.ends_at}`}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <div className="flex justify-end gap-2">
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            render={
                                                                <Link
                                                                    href={route(
                                                                        'admin.events.show',
                                                                        event.id,
                                                                    )}
                                                                />
                                                            }
                                                        >
                                                            View
                                                        </Button>
                                                        <Button
                                                            variant="outline"
                                                            size="sm"
                                                            render={
                                                                <Link
                                                                    href={route(
                                                                        'admin.events.edit',
                                                                        event.id,
                                                                    )}
                                                                />
                                                            }
                                                        >
                                                            Edit
                                                        </Button>
                                                    </div>
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>

                            {events.links.length > 3 && (
                                <div className="flex flex-wrap gap-1">
                                    {events.links.map((link, index) =>
                                        link.url ? (
                                            <Button
                                                key={index}
                                                variant={
                                                    link.active ? 'default' : 'outline'
                                                }
                                                size="sm"
                                                render={<Link href={link.url} />}
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        ) : (
                                            <Button
                                                key={index}
                                                variant="outline"
                                                size="sm"
                                                disabled
                                                dangerouslySetInnerHTML={{
                                                    __html: link.label,
                                                }}
                                            />
                                        ),
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}