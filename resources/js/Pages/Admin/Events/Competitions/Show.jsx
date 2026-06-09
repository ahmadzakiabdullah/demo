import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
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
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const TEAM_TYPE = 'App\\Models\\Team';
const ATHLETE_TYPE = 'App\\Models\\Athlete';

function AppealPanel({ event, match }) {
    const openAppeal = match.result?.appeals?.find((appeal) =>
        ['submitted', 'under_review'].includes(appeal.status),
    );
    const canResolve = match.can_resolve_appeal;

    const appealForm = useForm({
        reason: '',
        proposed_home_score: match.result?.home_score ?? 0,
        proposed_away_score: match.result?.away_score ?? 0,
    });

    const resolveForm = useForm({
        status: 'under_review',
        resolution_notes: '',
        proposed_home_score: openAppeal?.proposed_home_score ?? match.result?.home_score ?? 0,
        proposed_away_score: openAppeal?.proposed_away_score ?? match.result?.away_score ?? 0,
    });

    const submitAppeal = (e) => {
        e.preventDefault();
        appealForm.post(route('admin.events.results.appeals.store', [event.id, match.result.id]), {
            preserveScroll: true,
            onSuccess: () => appealForm.reset(),
        });
    };

    const resolveAppeal = (status) => {
        if (!openAppeal) {
            return;
        }

        router.patch(
            route('admin.events.appeals.status', [event.id, openAppeal.id]),
            {
                status,
                resolution_notes: resolveForm.data.resolution_notes,
                proposed_home_score: resolveForm.data.proposed_home_score,
                proposed_away_score: resolveForm.data.proposed_away_score,
            },
            { preserveScroll: true },
        );
    };

    if (!match.result) {
        return null;
    }

    const appealable = ['confirmed', 'published'].includes(match.result.status);

    return (
        <div className="mt-2 space-y-2 border-t pt-2">
            {match.result.appeals?.length > 0 && (
                <div className="space-y-1">
                    {match.result.appeals.map((appeal) => (
                        <p key={appeal.id} className="text-xs text-muted-foreground">
                            Appeal: {appeal.status}
                            {appeal.submitted_by ? ` by ${appeal.submitted_by.name}` : ''}
                            {appeal.reason ? ` — ${appeal.reason}` : ''}
                        </p>
                    ))}
                </div>
            )}

            {match.can_submit_appeal && appealable && !openAppeal && (
                <form onSubmit={submitAppeal} className="space-y-2">
                    <Input
                        placeholder="Appeal reason (min 10 chars)"
                        value={appealForm.data.reason}
                        onChange={(e) => appealForm.setData('reason', e.target.value)}
                    />
                    <div className="flex flex-wrap items-center gap-2">
                        <Input
                            type="number"
                            min={0}
                            className="w-16"
                            value={appealForm.data.proposed_home_score}
                            onChange={(e) =>
                                appealForm.setData('proposed_home_score', e.target.value)
                            }
                        />
                        <span className="text-xs text-muted-foreground">vs</span>
                        <Input
                            type="number"
                            min={0}
                            className="w-16"
                            value={appealForm.data.proposed_away_score}
                            onChange={(e) =>
                                appealForm.setData('proposed_away_score', e.target.value)
                            }
                        />
                        <Button type="submit" size="sm" variant="outline" disabled={appealForm.processing}>
                            Appeal
                        </Button>
                    </div>
                    <InputError message={appealForm.errors.reason || appealForm.errors.appeal} />
                </form>
            )}

            {openAppeal && canResolve && (
                <div className="space-y-2">
                    <Input
                        placeholder="Resolution notes"
                        value={resolveForm.data.resolution_notes}
                        onChange={(e) => resolveForm.setData('resolution_notes', e.target.value)}
                    />
                    <div className="flex flex-wrap gap-2">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            onClick={() => resolveAppeal('under_review')}
                        >
                            Review
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            onClick={() => resolveAppeal('upheld')}
                        >
                            Uphold
                        </Button>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            onClick={() => resolveAppeal('overturned')}
                        >
                            Overturn
                        </Button>
                    </div>
                </div>
            )}
        </div>
    );
}

function ResultForm({ event, competition, fixture, match }) {
    const form = useForm({
        home_score: match.result?.home_score ?? 0,
        away_score: match.result?.away_score ?? 0,
        notes: '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(
            route('admin.events.competitions.matches.result.store', [
                event.id,
                competition.id,
                fixture.id,
                match.id,
            ]),
            { preserveScroll: true },
        );
    };

    const advanceStatus = (status) => {
        if (!match.result?.id) {
            return;
        }

        router.patch(
            route('admin.events.results.status', [event.id, match.result.id]),
            { status },
            { preserveScroll: true },
        );
    };

    if (!match.can_enter_result) {
        return match.result ? (
            <div>
                <p className="text-sm text-muted-foreground">
                    {match.result.home_score} - {match.result.away_score} ({match.result.status})
                </p>
                <AppealPanel event={event} match={match} />
            </div>
        ) : null;
    }

    return (
        <div>
            <form onSubmit={submit} className="flex flex-wrap items-end gap-2">
                <Input
                    type="number"
                    min={0}
                    className="w-16"
                    value={form.data.home_score}
                    onChange={(e) => form.setData('home_score', e.target.value)}
                />
                <span className="text-sm text-muted-foreground">vs</span>
                <Input
                    type="number"
                    min={0}
                    className="w-16"
                    value={form.data.away_score}
                    onChange={(e) => form.setData('away_score', e.target.value)}
                />
                <Button type="submit" size="sm" disabled={form.processing}>
                    {match.result ? 'Update' : 'Save'}
                </Button>
                {match.result?.status === 'pending' && (
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={() => advanceStatus('confirmed')}
                    >
                        Confirm
                    </Button>
                )}
                {match.result?.status === 'confirmed' && (
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        onClick={() => advanceStatus('published')}
                    >
                        Publish
                    </Button>
                )}
                <InputError message={form.errors.home_score || form.errors.away_score} />
            </form>
            <AppealPanel event={event} match={match} />
        </div>
    );
}

function MatchForm({
    event,
    competition,
    fixture,
    venues,
    teams,
    athletes,
    officials,
    officialRoles,
    canManageSchedule,
}) {
    const [participantMode, setParticipantMode] = useState('team');
    const form = useForm({
        scheduled_at: '',
        duration_minutes: 60,
        venue_id: '',
        facility_id: '',
        home_participant_id: '',
        away_participant_id: '',
        official_id: '',
        official_role: 'referee',
        notes: '',
    });

    const facilities = useMemo(() => {
        const venue = venues.find(
            (item) => String(item.id) === String(form.data.venue_id),
        );
        return venue?.facilities ?? [];
    }, [venues, form.data.venue_id]);

    const participants = useMemo(() => {
        const type = participantMode === 'team' ? TEAM_TYPE : ATHLETE_TYPE;
        const pool = participantMode === 'team' ? teams : athletes;

        return [
            { side: 'home', participant_type: type, participant_id: form.data.home_participant_id },
            { side: 'away', participant_type: type, participant_id: form.data.away_participant_id },
        ];
    }, [participantMode, teams, athletes, form.data.home_participant_id, form.data.away_participant_id]);

    const submit = (e) => {
        e.preventDefault();

        form.transform((data) => ({
            scheduled_at: data.scheduled_at || null,
            duration_minutes: Number(data.duration_minutes),
            venue_id: data.venue_id || null,
            facility_id: data.facility_id || null,
            notes: data.notes || null,
            participants,
            officials: data.official_id
                ? [{ official_id: Number(data.official_id), role: data.official_role }]
                : [],
        }));

        form.post(
            route('admin.events.competitions.matches.store', [
                event.id,
                competition.id,
                fixture.id,
            ]),
            {
                preserveScroll: true,
                onSuccess: () => form.reset(),
            },
        );
    };

    if (!canManageSchedule) {
        return null;
    }

    return (
        <form onSubmit={submit} className="space-y-3 border-t pt-4">
            <p className="text-sm font-medium">Schedule match</p>
            <div className="grid gap-3 sm:grid-cols-2">
                <div>
                    <Label>Participant type</Label>
                    <Select value={participantMode} onValueChange={setParticipantMode}>
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="team">Teams</SelectItem>
                            <SelectItem value="athlete">Athletes</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div>
                    <Label htmlFor={`scheduled_${fixture.id}`}>Scheduled at</Label>
                    <Input
                        id={`scheduled_${fixture.id}`}
                        type="datetime-local"
                        value={form.data.scheduled_at}
                        onChange={(e) => form.setData('scheduled_at', e.target.value)}
                    />
                    <InputError message={form.errors.scheduled_at} />
                </div>
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
                <div>
                    <Label>Home</Label>
                    <Select
                        value={form.data.home_participant_id || ''}
                        onValueChange={(value) =>
                            form.setData('home_participant_id', value)
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select home" />
                        </SelectTrigger>
                        <SelectContent>
                            {(participantMode === 'team' ? teams : athletes).map((item) => (
                                <SelectItem key={item.id} value={String(item.id)}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
                <div>
                    <Label>Away</Label>
                    <Select
                        value={form.data.away_participant_id || ''}
                        onValueChange={(value) =>
                            form.setData('away_participant_id', value)
                        }
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select away" />
                        </SelectTrigger>
                        <SelectContent>
                            {(participantMode === 'team' ? teams : athletes).map((item) => (
                                <SelectItem key={item.id} value={String(item.id)}>
                                    {item.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <InputError message={form.errors.participants} />
            <div className="grid gap-3 sm:grid-cols-3">
                <div>
                    <Label>Venue</Label>
                    <Select
                        value={form.data.venue_id || ''}
                        onValueChange={(value) => {
                            form.setData({ ...form.data, venue_id: value, facility_id: '' });
                        }}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Select venue" />
                        </SelectTrigger>
                        <SelectContent>
                            {venues.map((venue) => (
                                <SelectItem key={venue.id} value={String(venue.id)}>
                                    {venue.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.venue_id} />
                </div>
                <div>
                    <Label>Facility</Label>
                    <Select
                        value={form.data.facility_id || ''}
                        onValueChange={(value) => form.setData('facility_id', value)}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Optional" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">None</SelectItem>
                            {facilities.map((facility) => (
                                <SelectItem key={facility.id} value={String(facility.id)}>
                                    {facility.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.facility_id} />
                </div>
                <div>
                    <Label>Duration (min)</Label>
                    <Input
                        type="number"
                        min={15}
                        max={480}
                        value={form.data.duration_minutes}
                        onChange={(e) =>
                            form.setData('duration_minutes', e.target.value)
                        }
                    />
                </div>
            </div>
            <div className="grid gap-3 sm:grid-cols-2">
                <div>
                    <Label>Official</Label>
                    <Select
                        value={form.data.official_id || ''}
                        onValueChange={(value) => form.setData('official_id', value)}
                    >
                        <SelectTrigger>
                            <SelectValue placeholder="Optional" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="">None</SelectItem>
                            {officials.map((official) => (
                                <SelectItem key={official.id} value={String(official.id)}>
                                    {official.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={form.errors.officials} />
                </div>
                <div>
                    <Label>Official role</Label>
                    <Select
                        value={form.data.official_role}
                        onValueChange={(value) => form.setData('official_role', value)}
                    >
                        <SelectTrigger>
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {officialRoles.map((role) => (
                                <SelectItem key={role} value={role}>
                                    {role.replace('_', ' ')}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <Button type="submit" size="sm" disabled={form.processing}>
                Add Match
            </Button>
        </form>
    );
}

export default function Show({
    event,
    competition,
    venues,
    teams,
    athletes,
    officials,
    officialRoles,
    canManageSchedule,
    bracket,
    supportsKnockoutPhase = false,
    scoreSchema,
}) {
    const { flash, errors } = usePage().props;

    const groupForm = useForm({ name: '', slug: '', sort_order: 0 });
    const fixtureForm = useForm({
        name: '',
        round: '',
        group_id: '',
        sort_order: 0,
    });

    const submitGroup = (e) => {
        e.preventDefault();
        groupForm.post(
            route('admin.events.competitions.groups.store', [event.id, competition.id]),
            { preserveScroll: true, onSuccess: () => groupForm.reset() },
        );
    };

    const submitFixture = (e) => {
        e.preventDefault();
        fixtureForm.post(
            route('admin.events.competitions.fixtures.store', [event.id, competition.id]),
            { preserveScroll: true, onSuccess: () => fixtureForm.reset() },
        );
    };

    const removeFixture = (fixtureId) => {
        router.delete(
            route('admin.events.competitions.fixtures.destroy', [
                event.id,
                competition.id,
                fixtureId,
            ]),
            { preserveScroll: true },
        );
    };

    const generateDraw = () => {
        router.post(
            route('admin.events.competitions.draw', [event.id, competition.id]),
            {},
            { preserveScroll: true },
        );
    };

    const generateKnockoutPhase = () => {
        router.post(
            route('admin.events.competitions.knockout-phase', [event.id, competition.id]),
            {},
            { preserveScroll: true },
        );
    };

    const removeMatch = (fixtureId, matchId) => {
        router.delete(
            route('admin.events.competitions.matches.destroy', [
                event.id,
                competition.id,
                fixtureId,
                matchId,
            ]),
            { preserveScroll: true },
        );
    };

    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                {
                    label: 'Competitions',
                    href: route('admin.events.competitions.index', event.id),
                },
                { label: competition.name },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">{competition.name}</h2>
                        <p className="text-sm text-muted-foreground">
                            {competition.sport?.name} · {competition.format?.name}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        {canManageSchedule && (
                            <Button variant="outline" onClick={generateDraw}>
                                Generate Draw
                            </Button>
                        )}
                        {canManageSchedule && supportsKnockoutPhase && (
                            <Button variant="outline" onClick={generateKnockoutPhase}>
                                Knockout Phase
                            </Button>
                        )}
                        <Button
                            variant="outline"
                            render={
                                <Link
                                    href={route('admin.events.rankings.index', event.id)}
                                />
                            }
                        >
                            Rankings
                        </Button>
                        <Button
                            variant="outline"
                            render={
                                <Link href={route('admin.events.medals.index', event.id)} />
                            }
                        >
                            Medals
                        </Button>
                        <Button
                            variant="outline"
                            render={
                                <Link
                                    href={route('admin.events.schedule.index', event.id)}
                                />
                            }
                        >
                            Schedule
                        </Button>
                        <Button
                            variant="outline"
                            render={
                                <Link
                                    href={route('admin.events.competitions.edit', [
                                        event.id,
                                        competition.id,
                                    ])}
                                />
                            }
                        >
                            Edit
                        </Button>
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
                            Back
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={`${competition.name} — ${event.name}`} />

            <div className="mx-auto max-w-7xl space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}
                {errors?.draw && (
                    <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {errors.draw}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Overview</CardTitle>
                    </CardHeader>
                    <CardContent className="flex flex-wrap gap-4">
                        <Badge variant="secondary">{competition.status}</Badge>
                        <span className="text-sm text-muted-foreground">
                            {competition.fixtures.length} fixture(s)
                        </span>
                    </CardContent>
                </Card>

                {competition.rankings?.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Standings</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>#</TableHead>
                                        <TableHead>Team</TableHead>
                                        <TableHead>Pts</TableHead>
                                        <TableHead>P</TableHead>
                                        <TableHead>W</TableHead>
                                        <TableHead>D</TableHead>
                                        <TableHead>L</TableHead>
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
                                            <TableCell>{row.goal_difference}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {bracket?.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Bracket</CardTitle>
                        </CardHeader>
                        <CardContent className="flex gap-4 overflow-x-auto pb-2">
                            {bracket.map((round) => (
                                <div
                                    key={round.round}
                                    className="min-w-56 space-y-3 rounded-lg border p-3"
                                >
                                    <p className="text-sm font-semibold">{round.round}</p>
                                    {round.matches.map((match) => (
                                        <div
                                            key={match.id}
                                            className="rounded-md bg-muted/40 p-2 text-sm"
                                        >
                                            {match.participants.map((participant) => (
                                                <p key={participant.side}>
                                                    {participant.name}
                                                    {match.result &&
                                                        ` (${participant.side === 'home' ? match.result.home_score : match.result.away_score})`}
                                                </p>
                                            ))}
                                            {!match.result && (
                                                <p className="text-muted-foreground">TBD</p>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}

                {competition.supports_groups && canManageSchedule && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Groups</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {competition.groups.length > 0 && (
                                <div className="flex flex-wrap gap-2">
                                    {competition.groups.map((group) => (
                                        <Badge key={group.id} variant="outline">
                                            {group.name}
                                        </Badge>
                                    ))}
                                </div>
                            )}
                            <form
                                onSubmit={submitGroup}
                                className="flex flex-col gap-3 sm:flex-row sm:items-end"
                            >
                                <div className="flex-1 space-y-2">
                                    <Label htmlFor="group_name">Group name</Label>
                                    <Input
                                        id="group_name"
                                        value={groupForm.data.name}
                                        onChange={(e) =>
                                            groupForm.setData('name', e.target.value)
                                        }
                                    />
                                    <InputError message={groupForm.errors.name} />
                                </div>
                                <Button type="submit" disabled={groupForm.processing}>
                                    Add Group
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {canManageSchedule && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Add fixture</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={submitFixture}
                                className="grid gap-3 sm:grid-cols-4 sm:items-end"
                            >
                                <div className="space-y-2">
                                    <Label htmlFor="fixture_name">Name</Label>
                                    <Input
                                        id="fixture_name"
                                        value={fixtureForm.data.name}
                                        onChange={(e) =>
                                            fixtureForm.setData('name', e.target.value)
                                        }
                                    />
                                    <InputError message={fixtureForm.errors.name} />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="fixture_round">Round</Label>
                                    <Input
                                        id="fixture_round"
                                        value={fixtureForm.data.round}
                                        onChange={(e) =>
                                            fixtureForm.setData('round', e.target.value)
                                        }
                                    />
                                </div>
                                {competition.supports_groups && (
                                    <div className="space-y-2">
                                        <Label>Group</Label>
                                        <Select
                                            value={fixtureForm.data.group_id || ''}
                                            onValueChange={(value) =>
                                                fixtureForm.setData('group_id', value)
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Optional" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="">None</SelectItem>
                                                {competition.groups.map((group) => (
                                                    <SelectItem
                                                        key={group.id}
                                                        value={String(group.id)}
                                                    >
                                                        {group.name}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                )}
                                <Button type="submit" disabled={fixtureForm.processing}>
                                    Add Fixture
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                {competition.fixtures.map((fixture) => (
                    <Card key={fixture.id}>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <div>
                                <CardTitle>{fixture.name}</CardTitle>
                                {fixture.round && (
                                    <p className="text-sm text-muted-foreground">
                                        {fixture.round}
                                        {fixture.group ? ` · ${fixture.group.name}` : ''}
                                    </p>
                                )}
                            </div>
                            {canManageSchedule && (
                                <Button
                                    variant="destructive"
                                    size="sm"
                                    onClick={() => removeFixture(fixture.id)}
                                >
                                    Remove fixture
                                </Button>
                            )}
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Schedule</TableHead>
                                        <TableHead>Matchup</TableHead>
                                        <TableHead>Venue</TableHead>
                                        <TableHead>Officials</TableHead>
                                        <TableHead>Result</TableHead>
                                        <TableHead>Status</TableHead>
                                        {canManageSchedule && (
                                            <TableHead className="text-right">
                                                Actions
                                            </TableHead>
                                        )}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {fixture.matches.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={canManageSchedule ? 7 : 6}
                                                className="text-center text-muted-foreground"
                                            >
                                                No matches scheduled yet.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        fixture.matches.map((match) => (
                                            <TableRow key={match.id}>
                                                <TableCell>
                                                    {match.scheduled_at ?? 'TBD'}
                                                </TableCell>
                                                <TableCell>
                                                    {match.participants
                                                        .map(
                                                            (participant) =>
                                                                `${participant.side}: ${participant.name}`,
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
                                                    {match.officials
                                                        .map(
                                                            (official) =>
                                                                `${official.name} (${official.role})`,
                                                        )
                                                        .join(', ') || '—'}
                                                </TableCell>
                                                <TableCell>
                                                    <ResultForm
                                                        event={event}
                                                        competition={competition}
                                                        fixture={fixture}
                                                        match={match}
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="secondary">
                                                        {match.status}
                                                    </Badge>
                                                </TableCell>
                                                {canManageSchedule && (
                                                    <TableCell className="text-right">
                                                        <Button
                                                            variant="destructive"
                                                            size="sm"
                                                            onClick={() =>
                                                                removeMatch(
                                                                    fixture.id,
                                                                    match.id,
                                                                )
                                                            }
                                                        >
                                                            Remove
                                                        </Button>
                                                    </TableCell>
                                                )}
                                            </TableRow>
                                        ))
                                    )}
                                </TableBody>
                            </Table>

                            <MatchForm
                                event={event}
                                competition={competition}
                                fixture={fixture}
                                venues={venues}
                                teams={teams}
                                athletes={athletes}
                                officials={officials}
                                officialRoles={officialRoles}
                                canManageSchedule={canManageSchedule}
                            />
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AuthenticatedLayout>
    );
}