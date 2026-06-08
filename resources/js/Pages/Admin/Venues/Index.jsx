import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
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

export default function Index({ venues, organization, filters }) {
    const { flash, auth } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const canManageVenues = auth.user?.can_view_venues;

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(
                route('admin.venues.index'),
                { search },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search]);

    return (
        <AuthenticatedLayout
            breadcrumbs={[{ label: 'Venues' }]}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">Venues</h2>
                        <p className="text-sm text-muted-foreground">
                            {organization.name}
                        </p>
                    </div>
                    {canManageVenues && (
                        <Button
                            render={
                                <Link href={route('admin.venues.create')} />
                            }
                        >
                            Add Venue
                        </Button>
                    )}
                </div>
            }
        >
            <Head title="Venues" />

            <div className="mx-auto max-w-7xl space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Organization venues</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <Input
                            placeholder="Search venues..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="max-w-sm"
                        />

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Address</TableHead>
                                    <TableHead>Capacity</TableHead>
                                    <TableHead>Facilities</TableHead>
                                    <TableHead>Timezone</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {venues.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="text-center text-muted-foreground"
                                        >
                                            No venues configured yet.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    venues.data.map((venue) => (
                                        <TableRow key={venue.id}>
                                            <TableCell className="font-medium">
                                                {venue.name}
                                            </TableCell>
                                            <TableCell>
                                                {venue.address || '—'}
                                            </TableCell>
                                            <TableCell>
                                                {venue.capacity ?? '—'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">
                                                    {venue.facilities_count}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>{venue.timezone}</TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    render={
                                                        <Link
                                                            href={route(
                                                                'admin.venues.show',
                                                                venue.id,
                                                            )}
                                                        />
                                                    }
                                                >
                                                    Manage
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}