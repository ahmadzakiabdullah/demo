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

export default function Edit({ event, athlete, genders }) {
    const { data, setData, put, processing, errors } = useForm({
        name: athlete.name,
        dob: athlete.dob || '',
        gender: athlete.gender || '',
        nationality: athlete.nationality || '',
        id_number: athlete.id_number || '',
        medical_clearance: athlete.medical_clearance,
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.events.athletes.update', [event.id, athlete.id]));
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                {
                    label: 'Athletes',
                    href: route('admin.events.athletes.index', event.id),
                },
                {
                    label: athlete.name,
                    href: route('admin.events.athletes.show', [
                        event.id,
                        athlete.id,
                    ]),
                },
                { label: 'Edit' },
            ]}
            header={<h2 className="text-xl font-semibold">Edit Athlete</h2>}
        >
            <Head title={`Edit ${athlete.name}`} />

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

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label htmlFor="dob">Date of birth</Label>
                                    <Input
                                        id="dob"
                                        type="date"
                                        value={data.dob}
                                        onChange={(e) =>
                                            setData('dob', e.target.value)
                                        }
                                    />
                                    <InputError message={errors.dob} />
                                </div>
                                <div>
                                    <Label htmlFor="gender">Gender</Label>
                                    <Select
                                        value={data.gender || ''}
                                        onValueChange={(value) =>
                                            setData('gender', value)
                                        }
                                    >
                                        <SelectTrigger id="gender">
                                            <SelectValue placeholder="Select gender" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {genders.map((gender) => (
                                                <SelectItem
                                                    key={gender}
                                                    value={gender}
                                                >
                                                    {gender}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.gender} />
                                </div>
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label htmlFor="nationality">
                                        Nationality
                                    </Label>
                                    <Input
                                        id="nationality"
                                        value={data.nationality}
                                        onChange={(e) =>
                                            setData(
                                                'nationality',
                                                e.target.value,
                                            )
                                        }
                                    />
                                    <InputError message={errors.nationality} />
                                </div>
                                <div>
                                    <Label htmlFor="id_number">ID number</Label>
                                    <Input
                                        id="id_number"
                                        value={data.id_number}
                                        onChange={(e) =>
                                            setData('id_number', e.target.value)
                                        }
                                    />
                                    <InputError message={errors.id_number} />
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <input
                                    id="medical_clearance"
                                    type="checkbox"
                                    checked={data.medical_clearance}
                                    onChange={(e) =>
                                        setData(
                                            'medical_clearance',
                                            e.target.checked,
                                        )
                                    }
                                    className="rounded border-input"
                                />
                                <Label htmlFor="medical_clearance">
                                    Medical clearance obtained
                                </Label>
                            </div>
                            <InputError message={errors.medical_clearance} />

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Save Changes
                                </Button>
                                <Button
                                    variant="outline"
                                    render={
                                        <Link
                                            href={route(
                                                'admin.events.athletes.show',
                                                [event.id, athlete.id],
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