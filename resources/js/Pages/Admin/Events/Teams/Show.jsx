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
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

function RegistrationStatusForm({ event, registration, statuses }) {
    const [status, setStatus] = useState('');
    const [rejectedReason, setRejectedReason] = useState('');

    const submit = (e) => {
        e.preventDefault();

        router.patch(
            route('admin.events.registrations.status', [
                event.id,
                registration.id,
            ]),
            { status, rejected_reason: rejectedReason },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setStatus('');
                    setRejectedReason('');
                },
            },
        );
    };

    return (
        <form
            onSubmit={submit}
            className="flex flex-col gap-4 border-t pt-4 sm:flex-row sm:items-end"
        >
            <div className="space-y-2 sm:w-48">
                <Label>Update status</Label>
                <Select value={status} onValueChange={setStatus}>
                    <SelectTrigger>
                        <SelectValue placeholder="Select status" />
                    </SelectTrigger>
                    <SelectContent>
                        {statuses.map((item) => (
                            <SelectItem key={item} value={item}>
                                {item}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>
            {status === 'rejected' && (
                <div className="flex-1 space-y-2">
                    <Label htmlFor={`rejected_reason_${registration.id}`}>
                        Rejection reason
                    </Label>
                    <Input
                        id={`rejected_reason_${registration.id}`}
                        value={rejectedReason}
                        onChange={(e) => setRejectedReason(e.target.value)}
                    />
                </div>
            )}
            <Button type="submit" disabled={!status}>
                Apply
            </Button>
        </form>
    );
}

export default function Show({
    event,
    team,
    registrations,
    availableAthletes,
    memberRoles,
    statuses,
    canManageRoster,
    canManageRegistrations,
}) {
    const { flash } = usePage().props;
    const rosterForm = useForm({
        athlete_id: '',
        role: 'member',
        jersey_number: '',
    });

    const addAthlete = (e) => {
        e.preventDefault();
        rosterForm.post(
            route('admin.events.teams.athletes.store', [event.id, team.id]),
            {
                preserveScroll: true,
                onSuccess: () => rosterForm.reset(),
            },
        );
    };

    const removeAthlete = (athleteId) => {
        router.delete(
            route('admin.events.teams.athletes.destroy', [
                event.id,
                team.id,
                athleteId,
            ]),
            { preserveScroll: true },
        );
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                {
                    label: 'Teams',
                    href: route('admin.events.teams.index', event.id),
                },
                { label: team.name },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">{team.name}</h2>
                        <p className="text-sm text-muted-foreground">
                            {team.sport?.name}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            render={
                                <Link
                                    href={route(
                                        'admin.events.teams.index',
                                        event.id,
                                    )}
                                />
                            }
                        >
                            Back
                        </Button>
                        <Button
                            render={
                                <Link
                                    href={route('admin.events.teams.edit', [
                                        event.id,
                                        team.id,
                                    ])}
                                />
                            }
                        >
                            Edit Team
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={`${team.name} — ${event.name}`} />

            <div className="mx-auto max-w-7xl space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Team details</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Coach
                                </p>
                                <p className="font-medium">
                                    {team.coach?.name || 'Not assigned'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Team manager
                                </p>
                                <p className="font-medium">
                                    {team.manager?.name || 'Not assigned'}
                                </p>
                            </div>
                            <div className="sm:col-span-2">
                                <p className="text-sm text-muted-foreground">
                                    Notes
                                </p>
                                <p className="font-medium">
                                    {team.notes || 'No notes.'}
                                </p>
                            </div>
                        </CardContent>
                    </Card>

                    {registrations.map((registration) => (
                        <Card key={registration.id}>
                            <CardHeader>
                                <CardTitle>Registration</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Status
                                    </p>
                                    <Badge>{registration.status}</Badge>
                                </div>
                                {registration.rejected_reason && (
                                    <div className="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                                        Rejected: {registration.rejected_reason}
                                    </div>
                                )}
                                {canManageRegistrations && (
                                    <RegistrationStatusForm
                                        event={event}
                                        registration={registration}
                                        statuses={statuses}
                                    />
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Roster</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {canManageRoster && availableAthletes.length > 0 && (
                            <form
                                onSubmit={addAthlete}
                                className="flex flex-col gap-4 border-b pb-6 sm:flex-row sm:items-end"
                            >
                                <div className="flex-1 space-y-2">
                                    <Label htmlFor="athlete_id">Add athlete</Label>
                                    <Select
                                        value={rosterForm.data.athlete_id || ''}
                                        onValueChange={(value) =>
                                            rosterForm.setData('athlete_id', value)
                                        }
                                    >
                                        <SelectTrigger id="athlete_id">
                                            <SelectValue placeholder="Select athlete" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableAthletes.map((athlete) => (
                                                <SelectItem
                                                    key={athlete.id}
                                                    value={String(athlete.id)}
                                                >
                                                    {athlete.name}
                                                    {athlete.id_number
                                                        ? ` (${athlete.id_number})`
                                                        : ''}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2 sm:w-40">
                                    <Label htmlFor="role">Role</Label>
                                    <Select
                                        value={rosterForm.data.role}
                                        onValueChange={(value) =>
                                            rosterForm.setData('role', value)
                                        }
                                    >
                                        <SelectTrigger id="role">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {memberRoles.map((role) => (
                                                <SelectItem key={role} value={role}>
                                                    {role}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="space-y-2 sm:w-28">
                                    <Label htmlFor="jersey_number">Jersey</Label>
                                    <Input
                                        id="jersey_number"
                                        value={rosterForm.data.jersey_number}
                                        onChange={(e) =>
                                            rosterForm.setData(
                                                'jersey_number',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    disabled={
                                        rosterForm.processing ||
                                        !rosterForm.data.athlete_id
                                    }
                                >
                                    Add
                                </Button>
                            </form>
                        )}

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>ID</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Jersey</TableHead>
                                    {canManageRoster && (
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {team.athletes.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={canManageRoster ? 5 : 4}
                                            className="text-center text-muted-foreground"
                                        >
                                            No athletes on roster yet.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    team.athletes.map((athlete) => (
                                        <TableRow key={athlete.id}>
                                            <TableCell className="font-medium">
                                                {athlete.name}
                                            </TableCell>
                                            <TableCell>
                                                {athlete.id_number || '—'}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">
                                                    {athlete.role}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {athlete.jersey_number || '—'}
                                            </TableCell>
                                            {canManageRoster && (
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        onClick={() =>
                                                            removeAthlete(
                                                                athlete.id,
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
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}