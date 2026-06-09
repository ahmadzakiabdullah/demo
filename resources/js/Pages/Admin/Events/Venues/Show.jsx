import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
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

export default function Show({
    event,
    venue,
    linkedSports,
    availableSports,
    canManageVenues,
}) {
    const { flash } = usePage().props;

    const linkForm = useForm({
        sport_id: '',
    });

    const submitLink = (e) => {
        e.preventDefault();
        linkForm.post(
            route('admin.events.venues.sports.store', [event.id, venue.id]),
            {
                preserveScroll: true,
                onSuccess: () => linkForm.reset(),
            },
        );
    };

    const unlinkSport = (sportId) => {
        router.delete(
            route('admin.events.venues.sports.destroy', [
                event.id,
                venue.id,
                sportId,
            ]),
            { preserveScroll: true },
        );
    };

    const detachVenue = () => {
        router.delete(
            route('admin.events.venues.destroy', [event.id, venue.id]),
        );
    };

    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                {
                    label: 'Venues',
                    href: route('admin.events.venues.index', event.id),
                },
                { label: venue.name },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">{venue.name}</h2>
                        <p className="text-sm text-muted-foreground">
                            {venue.address || 'No address set'}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        {venue.is_primary && <Badge>Primary venue</Badge>}
                        {canManageVenues && (
                            <Button
                                variant="destructive"
                                onClick={detachVenue}
                            >
                                Detach
                            </Button>
                        )}
                    </div>
                </div>
            }
        >
            <Head title={`${venue.name} — ${event.name}`} />

            <div className="mx-auto max-w-5xl space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Venue details</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Capacity
                            </p>
                            <p className="font-medium">
                                {venue.capacity ?? 'Not set'}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-muted-foreground">
                                Timezone
                            </p>
                            <p className="font-medium">{venue.timezone}</p>
                        </div>
                        <div className="sm:col-span-2">
                            <p className="text-sm text-muted-foreground">
                                Event notes
                            </p>
                            <p className="font-medium">
                                {venue.event_notes || 'No event-specific notes.'}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Facilities</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Capacity</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {venue.facilities.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={3}
                                            className="text-center text-muted-foreground"
                                        >
                                            No facilities configured.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    venue.facilities.map((facility) => (
                                        <TableRow key={facility.id}>
                                            <TableCell className="font-medium">
                                                {facility.name}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant="secondary">
                                                    {facility.type}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {facility.capacity ?? '—'}
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Linked sports</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {canManageVenues && availableSports.length > 0 && (
                            <form
                                onSubmit={submitLink}
                                className="flex flex-col gap-4 sm:flex-row sm:items-end"
                            >
                                <div className="flex-1 space-y-2">
                                    <Label htmlFor="sport_id">Sport</Label>
                                    <Select
                                        value={linkForm.data.sport_id || ''}
                                        onValueChange={(value) =>
                                            linkForm.setData('sport_id', value)
                                        }
                                    >
                                        <SelectTrigger id="sport_id">
                                            <SelectValue placeholder="Select sport to link" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableSports.map((sport) => (
                                                <SelectItem
                                                    key={sport.id}
                                                    value={String(sport.id)}
                                                >
                                                    {sport.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={linkForm.errors.sport_id}
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    disabled={linkForm.processing}
                                >
                                    Link sport
                                </Button>
                            </form>
                        )}

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Sport</TableHead>
                                    {canManageVenues && (
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {linkedSports.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={canManageVenues ? 2 : 1}
                                            className="text-center text-muted-foreground"
                                        >
                                            No sports linked to this venue yet.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    linkedSports.map((sport) => (
                                        <TableRow key={sport.id}>
                                            <TableCell className="font-medium">
                                                {sport.name}
                                            </TableCell>
                                            {canManageVenues && (
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        onClick={() =>
                                                            unlinkSport(
                                                                sport.id,
                                                            )
                                                        }
                                                    >
                                                        Unlink
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