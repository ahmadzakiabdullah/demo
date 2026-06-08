import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import InputError from '@/Components/InputError';
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
import { Head, Link, router, useForm } from '@inertiajs/react';

export default function Index({ event, ceremonies, sports, venues }) {
    const form = useForm({
        name: '',
        sport_id: '',
        venue_id: '',
        scheduled_at: '',
        duration_minutes: 60,
        notes: '',
    });

    const submit = (e) => {
        e.preventDefault();
        form.post(route('admin.events.medal-ceremonies.store', event.id), {
            preserveScroll: true,
            onSuccess: () => form.reset(),
        });
    };

    const removeCeremony = (id) => {
        router.delete(route('admin.events.medal-ceremonies.destroy', [event.id, id]), {
            preserveScroll: true,
        });
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                { label: 'Medal Ceremonies' },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">Medal Ceremonies</h2>
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
            <Head title={`Medal Ceremonies — ${event.name}`} />

            <div className="mx-auto max-w-5xl space-y-6">
                <Card>
                    <CardHeader>
                        <CardTitle>Schedule ceremony</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2">
                            <div className="sm:col-span-2">
                                <Label>Name</Label>
                                <Input
                                    value={form.data.name}
                                    onChange={(e) => form.setData('name', e.target.value)}
                                />
                                <InputError message={form.errors.name} />
                            </div>
                            <div>
                                <Label>Sport</Label>
                                <Select
                                    value={form.data.sport_id || 'none'}
                                    onValueChange={(value) =>
                                        form.setData('sport_id', value === 'none' ? '' : value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="All sports" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">All sports</SelectItem>
                                        {sports.map((sport) => (
                                            <SelectItem key={sport.id} value={String(sport.id)}>
                                                {sport.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Venue</Label>
                                <Select
                                    value={form.data.venue_id || 'none'}
                                    onValueChange={(value) =>
                                        form.setData('venue_id', value === 'none' ? '' : value)
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select venue" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="none">No venue</SelectItem>
                                        {venues.map((venue) => (
                                            <SelectItem key={venue.id} value={String(venue.id)}>
                                                {venue.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label>Scheduled at</Label>
                                <Input
                                    type="datetime-local"
                                    value={form.data.scheduled_at}
                                    onChange={(e) => form.setData('scheduled_at', e.target.value)}
                                />
                            </div>
                            <div>
                                <Label>Duration (minutes)</Label>
                                <Input
                                    type="number"
                                    min={15}
                                    value={form.data.duration_minutes}
                                    onChange={(e) =>
                                        form.setData('duration_minutes', e.target.value)
                                    }
                                />
                            </div>
                            <div className="sm:col-span-2">
                                <Button type="submit" disabled={form.processing}>
                                    Add ceremony
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Scheduled ceremonies</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {ceremonies.length === 0 ? (
                            <p className="text-sm text-muted-foreground">No ceremonies scheduled.</p>
                        ) : (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Sport</TableHead>
                                        <TableHead>Venue</TableHead>
                                        <TableHead>When</TableHead>
                                        <TableHead />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {ceremonies.map((ceremony) => (
                                        <TableRow key={ceremony.id}>
                                            <TableCell>{ceremony.name}</TableCell>
                                            <TableCell>{ceremony.sport?.name ?? 'All'}</TableCell>
                                            <TableCell>{ceremony.venue?.name ?? '—'}</TableCell>
                                            <TableCell>{ceremony.scheduled_at ?? 'TBD'}</TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    onClick={() => removeCeremony(ceremony.id)}
                                                >
                                                    Remove
                                                </Button>
                                            </TableCell>
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