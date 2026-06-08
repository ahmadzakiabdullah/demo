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

export default function Index({ event, sports, filters }) {
    const { flash, auth } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const canManageSports = auth.user?.can_view_sports;

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(
                route('admin.events.sports.index', event.id),
                { search },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, event.id]);

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                { label: 'Sports' },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">Sports</h2>
                        <p className="text-sm text-muted-foreground">{event.name}</p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            render={
                                <Link href={route('admin.events.show', event.id)} />
                            }
                        >
                            Back to Event
                        </Button>
                        {canManageSports && (
                            <Button
                                render={
                                    <Link
                                        href={route(
                                            'admin.events.sports.create',
                                            event.id,
                                        )}
                                    />
                                }
                            >
                                Add Sport
                            </Button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Sports — ${event.name}`} />

            <div className="mx-auto max-w-7xl space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Configured sports</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <Input
                            placeholder="Search sports..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="max-w-sm"
                        />

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Template</TableHead>
                                    <TableHead>Disciplines</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {sports.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="text-center text-muted-foreground"
                                        >
                                            No sports configured yet.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    sports.data.map((sport) => (
                                        <TableRow key={sport.id}>
                                            <TableCell className="font-medium">
                                                {sport.name}
                                            </TableCell>
                                            <TableCell>
                                                <Badge>{sport.status}</Badge>
                                            </TableCell>
                                            <TableCell>
                                                {sport.template_slug || '—'}
                                            </TableCell>
                                            <TableCell>
                                                {sport.disciplines_count}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    render={
                                                        <Link
                                                            href={route(
                                                                'admin.events.sports.show',
                                                                [event.id, sport.id],
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