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

export default function Index({
    event,
    participants,
    participantTypes,
    participantUnitLabel,
    filters,
}) {
    const { flash, auth } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const [type, setType] = useState(filters.type || '');

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(
                route('admin.events.participants.index', event.id),
                { search, type },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, type, event.id]);

    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                { label: participantUnitLabel },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">{participantUnitLabel}</h2>
                        <p className="text-sm text-muted-foreground">
                            {event.name} · {event.edition_year}
                        </p>
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
                        {auth.user?.can_view_event_participants && (
                            <>
                                <Button
                                    variant="outline"
                                    render={
                                        <Link
                                            href={route(
                                                'admin.events.participants.import',
                                                event.id,
                                            )}
                                        />
                                    }
                                >
                                    Import CSV
                                </Button>
                                <Button
                                    render={
                                        <Link
                                            href={route(
                                                'admin.events.participants.create',
                                                event.id,
                                            )}
                                        />
                                    }
                                >
                                    Add {participantUnitLabel}
                                </Button>
                            </>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`${participantUnitLabel} — ${event.name}`} />

            <div className="mx-auto max-w-7xl space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Registered {participantUnitLabel.toLowerCase()}s</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex flex-col gap-4 sm:flex-row">
                            <Input
                                placeholder="Search by name or code..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                className="sm:max-w-sm"
                            />
                            <Select
                                value={type || 'all'}
                                onValueChange={(value) =>
                                    setType(value === 'all' ? '' : value)
                                }
                            >
                                <SelectTrigger className="w-full sm:w-40">
                                    <SelectValue placeholder="All types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All types</SelectItem>
                                    {participantTypes.map((item) => (
                                        <SelectItem key={item} value={item}>
                                            {item}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Code</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Entries</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {participants.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="text-center text-muted-foreground"
                                        >
                                            No participants registered yet.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    participants.data.map((participant) => (
                                        <TableRow key={participant.id}>
                                            <TableCell className="font-medium">
                                                {participant.name}
                                            </TableCell>
                                            <TableCell>{participant.code ?? '—'}</TableCell>
                                            <TableCell>{participant.type}</TableCell>
                                            <TableCell>
                                                {participant.sport_entries_count}
                                            </TableCell>
                                            <TableCell>
                                                <Badge>{participant.status}</Badge>
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    render={
                                                        <Link
                                                            href={route(
                                                                'admin.events.participants.show',
                                                                [event.id, participant.id],
                                                            )}
                                                        />
                                                    }
                                                >
                                                    View
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