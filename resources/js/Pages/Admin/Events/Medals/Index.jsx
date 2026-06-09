import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

function MedalBadge({ type }) {
    const variants = {
        gold: 'default',
        silver: 'secondary',
        bronze: 'outline',
    };

    return <Badge variant={variants[type] ?? 'secondary'}>{type}</Badge>;
}

export default function Index({
    event,
    medals,
    tally,
    tallyByOrganization = [],
    tallyByCountry = [],
    tallyByContingent = [],
    sports,
    filters,
}) {
    const [sportId, setSportId] = useState(filters.sport_id || '');

    const applyFilter = (value) => {
        const next = value === 'all' ? '' : value;
        setSportId(next);
        router.get(route('admin.events.medals.index', event.id), { sport_id: next });
    };

    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                { label: 'Medals' },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">Medal Tally</h2>
                        <p className="text-sm text-muted-foreground">{event.name}</p>
                    </div>
                    <Button
                        variant="outline"
                        render={<Link href={route('admin.events.show', event.id)} />}
                    >
                        Back to Event
                    </Button>
                </div>
            }
        >
            <Head title={`Medals — ${event.name}`} />

            <div className="mx-auto max-w-7xl space-y-6">
                <Select value={sportId || 'all'} onValueChange={applyFilter}>
                    <SelectTrigger className="w-44">
                        <SelectValue placeholder="All sports" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="all">All sports</SelectItem>
                        {sports.map((sport) => (
                            <SelectItem key={sport.id} value={String(sport.id)}>
                                {sport.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>

                {[
                    { title: 'By recipient', rows: tally, key: 'recipient', label: 'Recipient' },
                    {
                        title: 'By organization',
                        rows: tallyByOrganization,
                        key: 'organization',
                        label: 'Organization',
                    },
                    { title: 'By country', rows: tallyByCountry, key: 'country', label: 'Country' },
                    {
                        title: 'By contingent (fakulti / negeri)',
                        rows: tallyByContingent,
                        key: 'contingent',
                        label: 'Contingent',
                    },
                ].map((table) => (
                    <Card key={table.key}>
                        <CardHeader>
                            <CardTitle>{table.title}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {table.rows.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No medals awarded yet.
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>{table.label}</TableHead>
                                            <TableHead>Gold</TableHead>
                                            <TableHead>Silver</TableHead>
                                            <TableHead>Bronze</TableHead>
                                            <TableHead>Total</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {table.rows.map((row) => (
                                            <TableRow key={`${table.key}-${row.label ?? row.recipient}`}>
                                                <TableCell className="font-medium">
                                                    {row.label ?? row.recipient}
                                                </TableCell>
                                                <TableCell>{row.gold}</TableCell>
                                                <TableCell>{row.silver}</TableCell>
                                                <TableCell>{row.bronze}</TableCell>
                                                <TableCell>{row.total}</TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                ))}

                <Card>
                    <CardHeader>
                        <CardTitle>All medals</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {medals.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No medals yet.</p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Medal</TableHead>
                                        <TableHead>Recipient</TableHead>
                                        <TableHead>Sport</TableHead>
                                        <TableHead>Competition</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {medals.map((medal) => (
                                        <TableRow key={medal.id}>
                                            <TableCell>
                                                <MedalBadge type={medal.type} />
                                            </TableCell>
                                            <TableCell>{medal.recipient}</TableCell>
                                            <TableCell>{medal.sport?.name}</TableCell>
                                            <TableCell>{medal.competition?.name}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}