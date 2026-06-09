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

export default function Show({ event, participant, sports, entryStatuses }) {
    const { flash } = usePage().props;
    const { data, setData, post, processing, errors, reset } = useForm({
        sport_id: '',
        status: 'approved',
        notes: '',
    });

    const submitEntry = (e) => {
        e.preventDefault();
        post(
            route('admin.events.participants.entries.store', [
                event.id,
                participant.id,
            ]),
            {
                preserveScroll: true,
                onSuccess: () => reset('sport_id', 'notes'),
            },
        );
    };

    const removeEntry = (entryId) => {
        if (!confirm('Remove this sport entry?')) {
            return;
        }

        router.delete(
            route('admin.events.participants.entries.destroy', [
                event.id,
                participant.id,
                entryId,
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
                    label: 'Participants',
                    href: route('admin.events.participants.index', event.id),
                },
                { label: participant.name },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">{participant.name}</h2>
                        <p className="text-sm text-muted-foreground">
                            {participant.code} · {participant.type}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            render={
                                <Link
                                    href={route(
                                        'admin.events.participants.edit',
                                        [event.id, participant.id],
                                    )}
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
                                        'admin.events.participants.index',
                                        event.id,
                                    )}
                                />
                            }
                        >
                            All Participants
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={`${participant.name} — ${event.name}`} />

            <div className="mx-auto max-w-7xl space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Sport Entries</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Sport</TableHead>
                                        <TableHead>Category</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {participant.sport_entries.length === 0 ? (
                                        <TableRow>
                                            <TableCell
                                                colSpan={4}
                                                className="text-center text-muted-foreground"
                                            >
                                                No sport entries yet.
                                            </TableCell>
                                        </TableRow>
                                    ) : (
                                        participant.sport_entries.map((entry) => (
                                            <TableRow key={entry.id}>
                                                <TableCell>
                                                    {entry.sport?.name}
                                                </TableCell>
                                                <TableCell>
                                                    {entry.sport_category?.name ?? '—'}
                                                </TableCell>
                                                <TableCell>
                                                    <Badge>{entry.status}</Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        onClick={() =>
                                                            removeEntry(entry.id)
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

                            <form
                                onSubmit={submitEntry}
                                className="grid gap-4 border-t pt-6 sm:grid-cols-2"
                            >
                                <div className="space-y-2 sm:col-span-2">
                                    <Label htmlFor="sport_id">Add sport entry</Label>
                                    <Select
                                        value={data.sport_id || ''}
                                        onValueChange={(value) =>
                                            setData('sport_id', value)
                                        }
                                    >
                                        <SelectTrigger id="sport_id">
                                            <SelectValue placeholder="Select sport" />
                                        </SelectTrigger>
                                        <SelectContent>
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
                                    <InputError message={errors.sport_id} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="status">Status</Label>
                                    <Select
                                        value={data.status}
                                        onValueChange={(value) =>
                                            setData('status', value)
                                        }
                                    >
                                        <SelectTrigger id="status">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {entryStatuses.map((status) => (
                                                <SelectItem key={status} value={status}>
                                                    {status}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.status} />
                                </div>

                                <div className="flex items-end">
                                    <Button type="submit" disabled={processing}>
                                        Add Entry
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Summary</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div>
                                <p className="text-2xl font-semibold">
                                    {participant.sport_entries.length}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    Sport entries
                                </p>
                            </div>
                            <div>
                                <p className="text-2xl font-semibold">
                                    {participant.teams_count}
                                </p>
                                <p className="text-sm text-muted-foreground">Teams</p>
                            </div>
                            <div>
                                <p className="text-2xl font-semibold">
                                    {participant.athletes_count}
                                </p>
                                <p className="text-sm text-muted-foreground">Athletes</p>
                            </div>
                            <Badge>{participant.status}</Badge>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}