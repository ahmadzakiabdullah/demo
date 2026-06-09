import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
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

export default function Index({ event, competitions, sports, filters }) {
    const [sportId, setSportId] = useState(filters.sport_id || '');

    const applyFilter = (value) => {
        const next = value === 'all' ? '' : value;
        setSportId(next);
        router.get(route('admin.events.rankings.index', event.id), {
            sport_id: next,
        });
    };

    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                { label: 'Rankings' },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">Rankings</h2>
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
            <Head title={`Rankings — ${event.name}`} />

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

                {competitions.length === 0 ? (
                    <Card>
                        <CardContent className="py-8 text-center text-muted-foreground">
                            No standings yet. Confirm match results to populate rankings.
                        </CardContent>
                    </Card>
                ) : (
                    competitions.map((competition) => (
                        <Card key={competition.id}>
                            <CardHeader>
                                <CardTitle>
                                    {competition.name}
                                    <span className="ml-2 text-sm font-normal text-muted-foreground">
                                        {competition.sport?.name} · {competition.format?.name}
                                    </span>
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                {competition.rankings.length === 0 ? (
                                    <p className="text-sm text-muted-foreground">
                                        No confirmed results yet.
                                    </p>
                                ) : (
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>#</TableHead>
                                                <TableHead>Name</TableHead>
                                                <TableHead>Pts</TableHead>
                                                <TableHead>P</TableHead>
                                                <TableHead>W</TableHead>
                                                <TableHead>D</TableHead>
                                                <TableHead>L</TableHead>
                                                <TableHead>GF</TableHead>
                                                <TableHead>GA</TableHead>
                                                <TableHead>GD</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {competition.rankings.map((row) => (
                                                <TableRow key={row.position}>
                                                    <TableCell>{row.position}</TableCell>
                                                    <TableCell className="font-medium">
                                                        {row.name}
                                                    </TableCell>
                                                    <TableCell>{row.points}</TableCell>
                                                    <TableCell>{row.played}</TableCell>
                                                    <TableCell>{row.won}</TableCell>
                                                    <TableCell>{row.drawn}</TableCell>
                                                    <TableCell>{row.lost}</TableCell>
                                                    <TableCell>{row.scored_for}</TableCell>
                                                    <TableCell>{row.scored_against}</TableCell>
                                                    <TableCell>{row.goal_difference}</TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                )}
                            </CardContent>
                        </Card>
                    ))
                )}
            </div>
        </AuthenticatedLayout>
    );
}