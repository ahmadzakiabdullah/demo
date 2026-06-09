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

export default function Show({ venue, facilityTypes, canManageFacilities }) {
    const { flash } = usePage().props;

    const facilityForm = useForm({
        name: '',
        slug: '',
        type: facilityTypes[0] ?? 'court',
        capacity: '',
    });

    const addFacility = (e) => {
        e.preventDefault();
        facilityForm.post(route('admin.venues.facilities.store', venue.id), {
            preserveScroll: true,
            onSuccess: () => facilityForm.reset(),
        });
    };

    const removeFacility = (facilityId) => {
        router.delete(
            route('admin.venues.facilities.destroy', [venue.id, facilityId]),
            { preserveScroll: true },
        );
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'Venues', href: route('admin.venues.index') },
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
                        <Button
                            variant="outline"
                            render={
                                <Link
                                    href={route('admin.venues.edit', venue.id)}
                                />
                            }
                        >
                            Edit
                        </Button>
                        <Button
                            variant="outline"
                            render={
                                <Link href={route('admin.venues.index')} />
                            }
                        >
                            Back
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={venue.name} />

            <div className="mx-auto max-w-5xl space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Overview</CardTitle>
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
                            <p className="text-sm text-muted-foreground">Notes</p>
                            <p className="font-medium">
                                {venue.notes || 'No notes yet.'}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Facilities</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        {canManageFacilities && (
                            <form
                                onSubmit={addFacility}
                                className="grid gap-4 sm:grid-cols-4"
                            >
                                <div className="sm:col-span-2">
                                    <Label htmlFor="facility_name">Name</Label>
                                    <Input
                                        id="facility_name"
                                        value={facilityForm.data.name}
                                        onChange={(e) =>
                                            facilityForm.setData(
                                                'name',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Court 1"
                                    />
                                    <InputError
                                        message={facilityForm.errors.name}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="facility_type">Type</Label>
                                    <Select
                                        value={facilityForm.data.type}
                                        onValueChange={(value) =>
                                            facilityForm.setData('type', value)
                                        }
                                    >
                                        <SelectTrigger id="facility_type">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {facilityTypes.map((type) => (
                                                <SelectItem
                                                    key={type}
                                                    value={type}
                                                >
                                                    {type}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={facilityForm.errors.type}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="facility_capacity">
                                        Capacity
                                    </Label>
                                    <Input
                                        id="facility_capacity"
                                        type="number"
                                        min="1"
                                        value={facilityForm.data.capacity}
                                        onChange={(e) =>
                                            facilityForm.setData(
                                                'capacity',
                                                e.target.value,
                                            )
                                        }
                                    />
                                </div>
                                <div className="sm:col-span-4">
                                    <Button
                                        type="submit"
                                        disabled={facilityForm.processing}
                                    >
                                        Add facility
                                    </Button>
                                </div>
                            </form>
                        )}

                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Capacity</TableHead>
                                    {canManageFacilities && (
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {venue.facilities.length === 0 ? (
                                    <TableRow>
                                        <TableCell
                                            colSpan={
                                                canManageFacilities ? 4 : 3
                                            }
                                            className="text-center text-muted-foreground"
                                        >
                                            No facilities added yet.
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
                                            {canManageFacilities && (
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="destructive"
                                                        size="sm"
                                                        onClick={() =>
                                                            removeFacility(
                                                                facility.id,
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

                <Card>
                    <CardHeader>
                        <CardTitle>Upcoming Bookings (Basic Availability Calendar)</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {venue.bookings && venue.bookings.length > 0 ? (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date/Time</TableHead>
                                        <TableHead>Duration</TableHead>
                                        <TableHead>Facility</TableHead>
                                        <TableHead>Event / Sport / Competition</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {venue.bookings.map((booking) => (
                                        <TableRow key={booking.id}>
                                            <TableCell>{booking.scheduled_at}</TableCell>
                                            <TableCell>{booking.duration_minutes} min</TableCell>
                                            <TableCell>
                                                {booking.facility?.name || '—'}
                                            </TableCell>
                                            <TableCell>
                                                {booking.event?.name} / {booking.sport?.name} / {booking.competition?.name}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        ) : (
                            <p className="text-sm text-muted-foreground">
                                No upcoming bookings. Venue is available.
                            </p>
                        )}
                        <p className="mt-2 text-xs text-muted-foreground">
                            Blocking is enforced automatically via conflict detection when scheduling matches.
                        </p>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}