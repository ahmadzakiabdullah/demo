import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

export default function Index({ event, days, sports, filters }) {
    const [date, setDate] = useState(filters.date || '');
    const [sportId, setSportId] = useState(filters.sport_id || '');

    const applyFilters = () => {
        router.get(
            route('admin.events.schedule.index', event.id),
            { date, sport_id: sportId },
            { preserveState: true },
        );
    };

    const shiftWeek = (direction) => {
        const current = new Date(date || filters.date);
        current.setDate(current.getDate() + direction * 7);
        const nextDate = current.toISOString().slice(0, 10);
        setDate(nextDate);
        router.get(
            route('admin.events.schedule.index', event.id),
            { date: nextDate, sport_id: sportId },
            { preserveState: true },
        );
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                { label: 'Schedule' },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">Event Schedule</h2>
                        <p className="text-sm text-muted-foreground">
                            Week view · {event.name}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            render={
                                <Link
                                    href={route(
                                        'admin.events.competitions.index',
                                        event.id,
                                    )}
                                />
                            }
                        >
                            Competitions
                        </Button>
                        <Button
                            variant="outline"
                            render={<Link href={route('admin.events.show', event.id)} />}
                        >
                            Back to Event
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={`Schedule — ${event.name}`} />

            <div className="mx-auto max-w-7xl space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Filters</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-col gap-4 sm:flex-row sm:items-end">
                        <div className="space-y-2">
                            <Label htmlFor="week_start">Week starting</Label>
                            <Input
                                id="week_start"
                                type="date"
                                value={date}
                                onChange={(e) => setDate(e.target.value)}
                            />
                        </div>
                        <div className="space-y-2">
                            <Label>Sport</Label>
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
                        <div className="flex gap-2">
                            <Button variant="outline" onClick={() => shiftWeek(-1)}>
                                Previous week
                            </Button>
                            <Button variant="outline" onClick={() => shiftWeek(1)}>
                                Next week
                            </Button>
                            <Button onClick={applyFilters}>Apply</Button>
                        </div>
                    </CardContent>
                </Card>

                {days.map((day) => (
                    <Card key={day.date}>
                        <CardHeader>
                            <CardTitle>{day.label}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {day.matches.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No matches scheduled.
                                </p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Time</TableHead>
                                            <TableHead>Sport</TableHead>
                                            <TableHead>Competition</TableHead>
                                            <TableHead>Matchup</TableHead>
                                            <TableHead>Venue</TableHead>
                                            <TableHead>Status</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {day.matches.map((match) => (
                                            <TableRow key={match.id}>
                                                <TableCell>
                                                    {match.scheduled_at
                                                        ? new Date(
                                                              match.scheduled_at,
                                                          ).toLocaleTimeString([], {
                                                              hour: '2-digit',
                                                              minute: '2-digit',
                                                          })
                                                        : 'TBD'}
                                                </TableCell>
                                                <TableCell>{match.sport?.name}</TableCell>
                                                <TableCell>
                                                    {match.competition?.name}
                                                </TableCell>
                                                <TableCell>
                                                    {match.participants
                                                        .map(
                                                            (participant) =>
                                                                participant.name,
                                                        )
                                                        .join(' vs ')}
                                                </TableCell>
                                                <TableCell>
                                                    {match.venue?.name ?? '—'}
                                                    {match.facility
                                                        ? ` / ${match.facility.name}`
                                                        : ''}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="secondary">
                                                        {match.status}
                                                    </Badge>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AuthenticatedLayout>
    );
}