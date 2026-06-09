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

export default function Index({ event, competitions, sports, statuses, filters }) {
    const { flash, auth } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [sportId, setSportId] = useState(filters.sport_id || '');

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(
                route('admin.events.competitions.index', event.id),
                { search, status, sport_id: sportId },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, status, sportId, event.id]);

    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                { label: 'Competitions' },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">Competitions</h2>
                        <p className="text-sm text-muted-foreground">{event.name}</p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            render={<Link href={route('admin.events.show', event.id)} />}
                        >
                            Back to Event
                        </Button>
                        {auth.user?.can_view_competitions && (
                            <Button
                                render={
                                    <Link
                                        href={route(
                                            'admin.events.competitions.create',
                                            event.id,
                                        )}
                                    />
                                }
                            >
                                New Competition
                            </Button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Competitions — ${event.name}`} />

            <div className="mx-auto max-w-7xl space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Event competitions</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex flex-col gap-3 sm:flex-row">
                            <Input
                                placeholder="Search competitions..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="max-w-sm"
                            />
                            <Select
                                value={status || 'all'}
                                onValueChange={(value) =>
                                    setStatus(value === 'all' ? '' : value)
                                }
                            >
                                <SelectTrigger className="w-44">
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
                            <Select
                                value={sportId || 'all'}
                                onValueChange={(value) =>
                                    setSportId(value === 'all' ? '' : value)
                                }
                            >
                                <SelectTrigger className="w-44">
                                    <SelectValue placeholder="All sports" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All sports</SelectItem>
                                    {sports.map((sport) => (
                                        <SelectItem
                                            key={sport.id}
                                            value={String(sport.id)}
                                        >
                                            {sport.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Sport</TableHead>
                                    <TableHead>Format</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Fixtures</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {competitions.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="text-center text-muted-foreground"
                                        >
                                            No competitions yet.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    competitions.data.map((competition) => (
                                        <TableRow key={competition.id}>
                                            <TableCell className="font-medium">
                                                {competition.name}
                                            </TableCell>
                                            <TableCell>
                                                {competition.sport?.name}
                                            </TableCell>
                                            <TableCell>
                                                {competition.format?.name}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">
                                                    {competition.status}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {competition.fixtures_count}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    render={
                                                        <Link
                                                            href={route(
                                                                'admin.events.competitions.show',
                                                                [event.id, competition.id],
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