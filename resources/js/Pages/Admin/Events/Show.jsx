import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
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

function StatusBadge({ status }) {
    return <Badge>{status}</Badge>;
}

export default function Show({
    event,
    assignmentRoles,
    organizationMembers,
}) {
    const { flash, auth } = usePage().props;
    const {
        data: assignmentData,
        setData: setAssignmentData,
        post: postAssignment,
        processing: assigning,
        errors: assignmentErrors,
        reset: resetAssignment,
    } = useForm({
        user_id: '',
        role: assignmentRoles[0] ?? 'event_organizer',
    });

    const submitAssignment = (e) => {
        e.preventDefault();
        postAssignment(route('admin.events.assignments.store', event.id), {
            preserveScroll: true,
            onSuccess: () => resetAssignment('user_id'),
        });
    };

    const removeAssignment = (userId) => {
        router.delete(route('admin.events.assignments.destroy', [event.id, userId]), {
            preserveScroll: true,
        });
    };

    const assignedUserIds = event.assignees.map((assignee) => assignee.id);
    const availableMembers = organizationMembers.filter(
        (member) => !assignedUserIds.includes(member.id),
    );

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold leading-tight text-foreground">
                            {event.name}
                        </h2>
                        <p className="text-sm text-muted-foreground">
                            {event.organization?.name}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            render={<Link href={route('admin.events.index')} />}
                        >
                            Back
                        </Button>
                        {auth.user?.can_view_sports && (
                            <Button
                                variant="outline"
                                render={
                                    <Link
                                        href={route(
                                            'admin.events.sports.index',
                                            event.id,
                                        )}
                                    />
                                }
                            >
                                Sports
                            </Button>
                        )}
                        {auth.user?.can_view_athletes && (
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
                                Athletes
                            </Button>
                        )}
                        {auth.user?.can_view_teams && (
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
                                Teams
                            </Button>
                        )}
                        {auth.user?.can_view_officials && (
                            <Button
                                variant="outline"
                                render={
                                    <Link
                                        href={route(
                                            'admin.events.officials.index',
                                            event.id,
                                        )}
                                    />
                                }
                            >
                                Officials
                            </Button>
                        )}
                        {auth.user?.can_view_venues && (
                            <Button
                                variant="outline"
                                render={
                                    <Link
                                        href={route(
                                            'admin.events.venues.index',
                                            event.id,
                                        )}
                                    />
                                }
                            >
                                Venues
                            </Button>
                        )}
                        {auth.user?.can_view_competitions && (
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
                        )}
                        {auth.user?.can_view_competitions && (
                            <Button
                                variant="outline"
                                render={
                                    <Link
                                        href={route(
                                            'admin.events.schedule.index',
                                            event.id,
                                        )}
                                    />
                                }
                            >
                                Schedule
                            </Button>
                        )}
                        {auth.user?.can_view_results && (
                            <Button
                                variant="outline"
                                render={
                                    <Link
                                        href={route(
                                            'admin.events.rankings.index',
                                            event.id,
                                        )}
                                    />
                                }
                            >
                                Rankings
                            </Button>
                        )}
                        {auth.user?.can_view_results && (
                            <Button
                                variant="outline"
                                render={
                                    <Link
                                        href={route('admin.events.medals.index', event.id)}
                                    />
                                }
                            >
                                Medals
                            </Button>
                        )}
                        <Button
                            render={<Link href={route('admin.events.edit', event.id)} />}
                        >
                            Edit Event
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={event.name} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            {flash.success}
                        </div>
                    )}

                    <div className="grid gap-6 lg:grid-cols-3">
                        <Card className="lg:col-span-2">
                            <CardHeader>
                                <CardTitle>Overview</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <p className="text-sm text-muted-foreground">Status</p>
                                    <StatusBadge status={event.status} />
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Type</p>
                                    <p className="font-medium">{event.event_type?.name}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Category</p>
                                    <p className="font-medium">
                                        {event.event_category?.name}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Location</p>
                                    <p className="font-medium">
                                        {event.location || 'Not set'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Starts</p>
                                    <p className="font-medium">
                                        {event.starts_at ?? 'Not scheduled'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Ends</p>
                                    <p className="font-medium">
                                        {event.ends_at ?? 'Not scheduled'}
                                    </p>
                                </div>
                                <div className="sm:col-span-2">
                                    <p className="text-sm text-muted-foreground">
                                        Description
                                    </p>
                                    <p className="font-medium">
                                        {event.description || 'No description yet.'}
                                    </p>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Summary</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <p className="text-2xl font-semibold">
                                        {event.stats.participants_count}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Participants
                                    </p>
                                </div>
                                <div>
                                    <p className="text-2xl font-semibold">
                                        {event.stats.sports_count}
                                    </p>
                                    <p className="text-sm text-muted-foreground">Sports</p>
                                </div>
                                <div>
                                    <p className="text-2xl font-semibold">
                                        {event.stats.fixtures_count}
                                    </p>
                                    <p className="text-sm text-muted-foreground">
                                        Fixtures scheduled
                                    </p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>

                    <Card>
                        <CardHeader>
                            <CardTitle>Event Team</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <form
                                onSubmit={submitAssignment}
                                className="flex flex-col gap-4 sm:flex-row sm:items-end"
                            >
                                <div className="flex-1 space-y-2">
                                    <Label htmlFor="user_id">Assign Member</Label>
                                    <Select
                                        value={assignmentData.user_id || ''}
                                        onValueChange={(value) =>
                                            setAssignmentData('user_id', value)
                                        }
                                    >
                                        <SelectTrigger id="user_id" className="w-full">
                                            <SelectValue placeholder="Select organization member" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableMembers.map((member) => (
                                                <SelectItem
                                                    key={member.id}
                                                    value={String(member.id)}
                                                >
                                                    {member.name} ({member.email})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    {assignmentErrors.user_id && (
                                        <p className="text-sm text-destructive">
                                            {assignmentErrors.user_id}
                                        </p>
                                    )}
                                </div>
                                <div className="space-y-2 sm:w-56">
                                    <Label htmlFor="role">Role</Label>
                                    <Select
                                        value={assignmentData.role}
                                        onValueChange={(value) =>
                                            setAssignmentData('role', value)
                                        }
                                    >
                                        <SelectTrigger id="role" className="w-full">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {assignmentRoles.map((role) => (
                                                <SelectItem key={role} value={role}>
                                                    {role.replace('_', ' ')}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                                <Button type="submit" disabled={assigning}>
                                    Assign
                                </Button>
                            </form>

                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {event.assignees.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={4}
                                                className="text-center text-muted-foreground"
                                            >
                                                No team members assigned yet.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        event.assignees.map((assignee) => (
                                            <TableRow key={assignee.id}>
                                                <TableCell className="font-medium">
                                                    {assignee.name}
                                                </TableCell>
                                                <TableCell>{assignee.email}</TableCell>
                                                <TableCell>
                                                    <Badge variant="secondary">
                                                        {assignee.role.replace('_', ' ')}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        onClick={() =>
                                                            removeAssignment(assignee.id)
                                                        }
                                                    >
                                                        Remove
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
            </div>
        </AuthenticatedLayout>
    );
}