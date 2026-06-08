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

export default function Edit({ event, competition, formats, statuses }) {
    const { data, setData, put, processing, errors } = useForm({
        competition_format_id: String(competition.format?.id ?? ''),
        name: competition.name,
        slug: competition.slug,
        status: competition.status,
        notes: competition.notes ?? '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.events.competitions.update', [event.id, competition.id]));
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                {
                    label: 'Competitions',
                    href: route('admin.events.competitions.index', event.id),
                },
                { label: competition.name },
            ]}
            header={<h2 className="text-xl font-semibold">Edit Competition</h2>}
        >
            <Head title={`Edit ${competition.name}`} />

            <div className="mx-auto max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle>{competition.name}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <Label>Sport</Label>
                                <p className="text-sm font-medium">
                                    {competition.sport?.name}
                                </p>
                            </div>

                            <div>
                                <Label htmlFor="competition_format_id">Format</Label>
                                <Select
                                    value={data.competition_format_id}
                                    onValueChange={(value) =>
                                        setData('competition_format_id', value)
                                    }
                                >
                                    <SelectTrigger id="competition_format_id">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {formats.map((format) => (
                                            <SelectItem
                                                key={format.id}
                                                value={String(format.id)}
                                            >
                                                {format.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.competition_format_id} />
                            </div>

                            <div>
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div>
                                <Label htmlFor="slug">Slug</Label>
                                <Input
                                    id="slug"
                                    value={data.slug}
                                    onChange={(e) => setData('slug', e.target.value)}
                                />
                                <InputError message={errors.slug} />
                            </div>

                            <div>
                                <Label htmlFor="status">Status</Label>
                                <Select
                                    value={data.status}
                                    onValueChange={(value) => setData('status', value)}
                                >
                                    <SelectTrigger id="status">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statuses.map((item) => (
                                            <SelectItem key={item} value={item}>
                                                {item}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>

                            <div>
                                <Label htmlFor="notes">Notes</Label>
                                <Input
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                />
                                <InputError message={errors.notes} />
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Save Changes
                                </Button>
                                <Button
                                    variant="outline"
                                    render={
                                        <Link
                                            href={route(
                                                'admin.events.competitions.show',
                                                [event.id, competition.id],
                                            )}
                                        />
                                    }
                                >
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