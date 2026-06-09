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
import { Head, Link, useForm, usePage } from '@inertiajs/react';

export default function Index({
    event,
    venues,
    availableVenues,
    canManageVenues,
}) {
    const { flash } = usePage().props;

    const attachForm = useForm({
        venue_id: '',
        is_primary: false,
        notes: '',
    });

    const submitAttach = (e) => {
        e.preventDefault();
        attachForm.post(route('admin.events.venues.store', event.id), {
            preserveScroll: true,
            onSuccess: () => attachForm.reset(),
        });
    };

    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                { label: 'Venues' },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">Event Venues</h2>
                        <p className="text-sm text-muted-foreground">
                            {event.name}
                        </p>
                    </div>
                    <Button
                        variant="outline"
                        render={
                            <Link href={route('admin.events.show', event.id)} />
                        }
                    >
                        Back to Event
                    </Button>
                </div>
            }
        >
            <Head title={`Venues — ${event.name}`} />

            <div className="mx-auto max-w-7xl space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                {canManageVenues && availableVenues.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Attach venue</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form
                                onSubmit={submitAttach}
                                className="flex flex-col gap-4 sm:flex-row sm:items-end"
                            >
                                <div className="flex-1 space-y-2">
                                    <Label htmlFor="venue_id">Venue</Label>
                                    <Select
                                        value={attachForm.data.venue_id || ''}
                                        onValueChange={(value) =>
                                            attachForm.setData('venue_id', value)
                                        }
                                    >
                                        <SelectTrigger id="venue_id">
                                            <SelectValue placeholder="Select organization venue" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {availableVenues.map((venue) => (
                                                <SelectItem
                                                    key={venue.id}
                                                    value={String(venue.id)}
                                                >
                                                    {venue.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={attachForm.errors.venue_id}
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    disabled={attachForm.processing}
                                >
                                    Attach
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Attached venues</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Address</TableHead>
                                    <TableHead>Facilities</TableHead>
                                    <TableHead>Primary</TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {venues.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="text-center text-muted-foreground"
                                        >
                                            No venues attached to this event yet.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    venues.map((venue) => (
                                        <TableRow key={venue.id}>
                                            <TableCell className="font-medium">
                                                {venue.name}
                                            </TableCell>
                                            <TableCell>
                                                {venue.address || '—'}
                                            </TableCell>
                                            <TableCell>
                                                {venue.facilities_count}
                                            </TableCell>
                                            <TableCell>
                                                {venue.is_primary ? (
                                                    <Badge>Primary</Badge>
                                                ) : (
                                                    '—'
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    render={
                                                        <Link
                                                            href={route(
                                                                'admin.events.venues.show',
                                                                [
                                                                    event.id,
                                                                    venue.id,
                                                                ],
                                                            )}
                                                        />
                                                    }
                                                >
                                                    Manage
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