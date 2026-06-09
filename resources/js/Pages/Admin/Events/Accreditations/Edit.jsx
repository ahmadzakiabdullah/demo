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

export default function Edit({ event, accreditation, types }) {
    const { data, setData, put, processing, errors } = useForm({
        status: accreditation.status,
        notes: accreditation.notes || '',
        expires_at: accreditation.expires_at || '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.events.accreditations.update', [event.id, accreditation.id]));
    };

    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                { label: 'Accreditations', href: route('admin.events.accreditations.index', event.id) },
                { label: accreditation.id, href: route('admin.events.accreditations.show', [event.id, accreditation.id]) },
                { label: 'Edit' },
            ]}
            header={<h2 className="text-xl font-semibold">Edit Accreditation</h2>}
        >
            <Head title={`Edit Accreditation — ${event.name}`} />

            <div className="mx-auto max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle>Update Accreditation</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <Label>Status</Label>
                                <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="active">Active</SelectItem>
                                        <SelectItem value="revoked">Revoked</SelectItem>
                                        <SelectItem value="expired">Expired</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>

                            <div>
                                <Label>Notes</Label>
                                <Input value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                                <InputError message={errors.notes} />
                            </div>

                            <div>
                                <Label>Expires At</Label>
                                <Input type="datetime-local" value={data.expires_at} onChange={(e) => setData('expires_at', e.target.value)} />
                                <InputError message={errors.expires_at} />
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Save Changes
                                </Button>
                                <Button variant="outline" render={<Link href={route('admin.events.accreditations.show', [event.id, accreditation.id])} />}>
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
