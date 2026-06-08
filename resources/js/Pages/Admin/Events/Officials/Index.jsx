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

function formatType(type) {
    return type.replace(/_/g, ' ');
}

export default function Index({ event, officials, sports, statuses, types, filters }) {
    const { flash, auth } = usePage().props;
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [sportId, setSportId] = useState(filters.sport_id || '');
    const [type, setType] = useState(filters.type || '');
    const canManageOfficials = auth.user?.can_view_officials;

    useEffect(() => {
        const timeout = setTimeout(() => {
            router.get(
                route('admin.events.officials.index', event.id),
                { search, status, sport_id: sportId, type },
                { preserveState: true, replace: true },
            );
        }, 300);

        return () => clearTimeout(timeout);
    }, [search, status, sportId, type, event.id]);

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                { label: 'Officials' },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">Officials</h2>
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
                        {canManageOfficials && (
                            <Button
                                render={
                                    <Link
                                        href={route(
                                            'admin.events.officials.create',
                                            event.id,
                                        )}
                                    />
                                }
                            >
                                Register Official
                            </Button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`Officials — ${event.name}`} />

            <div className="mx-auto max-w-7xl space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Registered officials</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex flex-col gap-3 sm:flex-row">
                            <Input
                                placeholder="Search by name, email, or certification..."
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
                            <Select
                                value={type || 'all'}
                                onValueChange={(value) =>
                                    setType(value === 'all' ? '' : value)
                                }
                            >
                                <SelectTrigger className="w-44">
                                    <SelectValue placeholder="All types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All types</SelectItem>
                                    {types.map((item) => (
                                        <SelectItem key={item} value={item}>
                                            {formatType(item)}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Sport</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Certification</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {officials.data.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="text-center text-muted-foreground"
                                        >
                                            No officials registered yet.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    officials.data.map((official) => {
                                        const registration = official.registrations[0];
                                        const certExpired =
                                            official.certification_expires_at &&
                                            new Date(
                                                official.certification_expires_at,
                                            ) < new Date();

                                        return (
                                            <TableRow key={official.id}>
                                                <TableCell className="font-medium">
                                                    {official.name}
                                                </TableCell>
                                                <TableCell>
                                                    {formatType(official.type)}
                                                </TableCell>
                                                <TableCell>
                                                    {registration?.sport?.name || '—'}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge>
                                                        {registration?.status || '—'}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {official.certification_level
                                                        ? certExpired
                                                            ? 'Expired'
                                                            : official.certification_level
                                                        : '—'}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        render={
                                                            <Link
                                                                href={route(
                                                                    'admin.events.officials.show',
                                                                    [
                                                                        event.id,
                                                                        official.id,
                                                                    ],
                                                                )}
                                                            />
                                                        }
                                                    >
                                                        View
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        );
                                    })
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}