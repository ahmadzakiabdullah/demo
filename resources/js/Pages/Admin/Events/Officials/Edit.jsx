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

function formatType(type) {
    return type.replace(/_/g, ' ');
}

export default function Edit({ event, official, types }) {
    const { data, setData, put, processing, errors } = useForm({
        name: official.name,
        email: official.email || '',
        type: official.type,
        certification_level: official.certification_level || '',
        certification_expires_at: official.certification_expires_at || '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.events.officials.update', [event.id, official.id]));
    };

    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                {
                    label: 'Officials',
                    href: route('admin.events.officials.index', event.id),
                },
                {
                    label: official.name,
                    href: route('admin.events.officials.show', [
                        event.id,
                        official.id,
                    ]),
                },
                { label: 'Edit' },
            ]}
            header={<h2 className="text-xl font-semibold">Edit Official</h2>}
        >
            <Head title={`Edit ${official.name}`} />

            <div className="mx-auto max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle>Update profile</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <Label htmlFor="name">Full name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) =>
                                        setData('name', e.target.value)
                                    }
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div>
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) =>
                                        setData('email', e.target.value)
                                    }
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div>
                                <Label htmlFor="type">Official type</Label>
                                <Select
                                    value={data.type || ''}
                                    onValueChange={(value) =>
                                        setData('type', value)
                                    }
                                >
                                    <SelectTrigger id="type">
                                        <SelectValue placeholder="Select type" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {types.map((item) => (
                                            <SelectItem key={item} value={item}>
                                                {formatType(item)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.type} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label htmlFor="certification_level">
                                        Certification level
                                    </Label>
                                    <Input
                                        id="certification_level"
                                        value={data.certification_level}
                                        onChange={(e) =>
                                            setData(
                                                'certification_level',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={errors.certification_level}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="certification_expires_at">
                                        Certification expires
                                    </Label>
                                    <Input
                                        id="certification_expires_at"
                                        type="date"
                                        value={data.certification_expires_at}
                                        onChange={(e) =>
                                            setData(
                                                'certification_expires_at',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError
                                        message={errors.certification_expires_at}
                                    />
                                </div>
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
                                                'admin.events.officials.show',
                                                [event.id, official.id],
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