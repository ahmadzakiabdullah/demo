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
import { Head, Link, router, usePage } from '@inertiajs/react';
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
            {
                status,
                rejected_reason: rejectedReason,
            },
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
    athlete,
    registrations,
    history,
    statuses,
    canManageRegistrations,
}) {
    const { flash } = usePage().props;

    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                {
                    label: 'Athletes',
                    href: route('admin.events.athletes.index', event.id),
                },
                { label: athlete.name },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">{athlete.name}</h2>
                        <p className="text-sm text-muted-foreground">
                            Athlete profile
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            render={
                                <Link
                                    href={route(
                                        'admin.events.athletes.index',
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
                                    href={route(
                                        'admin.events.athletes.edit',
                                        [event.id, athlete.id],
                                    )}
                                />
                            }
                        >
                            Edit Profile
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={`${athlete.name} — ${event.name}`} />

            <div className="mx-auto max-w-7xl space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Profile</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Date of birth
                                </p>
                                <p className="font-medium">
                                    {athlete.dob || 'Not set'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Gender
                                </p>
                                <p className="font-medium">
                                    {athlete.gender || 'Not set'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Nationality
                                </p>
                                <p className="font-medium">
                                    {athlete.nationality || 'Not set'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    ID number
                                </p>
                                <p className="font-medium">
                                    {athlete.id_number || 'Not set'}
                                </p>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">
                                    Medical clearance
                                </p>
                                <Badge
                                    variant={
                                        athlete.medical_clearance
                                            ? 'default'
                                            : 'secondary'
                                    }
                                >
                                    {athlete.medical_clearance
                                        ? 'Cleared'
                                        : 'Pending'}
                                </Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Participation history</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {history.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No prior event registrations.
                                </p>
                            ) : (
                                <ul className="space-y-3">
                                    {history.map((item) => (
                                        <li
                                            key={item.id}
                                            className="rounded-md border p-3 text-sm"
                                        >
                                            <p className="font-medium">
                                                {item.event?.name}
                                            </p>
                                            <p className="text-muted-foreground">
                                                {item.sport?.name} — {item.status}
                                            </p>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {registrations.map((registration) => (
                    <Card key={registration.id}>
                        <CardHeader>
                            <CardTitle>
                                Registration — {registration.sport?.name}
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex flex-wrap gap-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Status
                                    </p>
                                    <Badge>{registration.status}</Badge>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Category
                                    </p>
                                    <p className="font-medium">
                                        {registration.sport_category?.name ||
                                            '—'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">
                                        Division
                                    </p>
                                    <p className="font-medium">
                                        {registration.sport_division?.name ||
                                            '—'}
                                    </p>
                                </div>
                            </div>

                            {registration.eligibility_issues?.length > 0 && (
                                <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                                    <p className="font-medium">
                                        Eligibility issues
                                    </p>
                                    <ul className="mt-1 list-disc pl-5">
                                        {registration.eligibility_issues.map(
                                            (issue) => (
                                                <li key={issue}>{issue}</li>
                                            ),
                                        )}
                                    </ul>
                                </div>
                            )}

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

                {history.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>All past events</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Event</TableHead>
                                        <TableHead>Sport</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Approved</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {history.map((item) => (
                                        <TableRow key={item.id}>
                                            <TableCell>
                                                {item.event?.name}
                                            </TableCell>
                                            <TableCell>
                                                {item.sport?.name}
                                            </TableCell>
                                            <TableCell>
                                                <Badge>{item.status}</Badge>
                                            </TableCell>
                                            <TableCell>
                                                {item.approved_at || '—'}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AuthenticatedLayout>
    );
}