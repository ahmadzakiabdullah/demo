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
import { Head, Link, useForm } from '@inertiajs/react';

export default function Create({ event, participants, types }) {
    const { data, setData, post, processing, errors } = useForm({
        accreditable_type: 'App\\Models\\EventParticipant',
        accreditable_id: '',
        type: 'athlete',
        notes: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.events.accreditations.store', event.id));
    };

    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                { label: 'Accreditations', href: route('admin.events.accreditations.index', event.id) },
                { label: 'Issue' },
            ]}
            header={<h2 className="text-xl font-semibold">Issue Accreditation</h2>}
        >
            <Head title={`Issue Accreditation — ${event.name}`} />

            <div className="mx-auto max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle>Issue New Accreditation</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <Label>Accreditable Type</Label>
                                <Select value={data.accreditable_type} onValueChange={(value) => setData('accreditable_type', value)}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="App\\Models\\EventParticipant">Event Participant</SelectItem>
                                        <SelectItem value="App\\Models\\Team">Team</SelectItem>
                                        <SelectItem value="App\\Models\\Athlete">Athlete</SelectItem>
                                        <SelectItem value="App\\Models\\Official">Official</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.accreditable_type} />
                            </div>

                            <div>
                                <Label>Accreditable</Label>
                                <Select value={data.accreditable_id} onValueChange={(value) => setData('accreditable_id', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select..." />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {participants.map((p) => (
                                            <SelectItem key={p.id} value={String(p.id)}>
                                                {p.name} ({p.type})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.accreditable_id} />
                            </div>

                            <div>
                                <Label>Type</Label>
                                <Select value={data.type} onValueChange={(value) => setData('type', value)}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {types.map((t) => (
                                            <SelectItem key={t} value={t}>
                                                {t}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.type} />
                            </div>

                            <div>
                                <Label>Notes</Label>
                                <Input value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                                <InputError message={errors.notes} />
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Issue Accreditation
                                </Button>
                                <Button variant="outline" render={<Link href={route('admin.events.accreditations.index', event.id)} />}>
                                    Cancel
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AuthenticatedLayout>
    );
}
