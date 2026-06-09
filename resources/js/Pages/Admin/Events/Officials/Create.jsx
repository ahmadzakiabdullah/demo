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
import { useMemo } from 'react';

function formatType(type) {
    return type.replace(/_/g, ' ');
}

export default function Create({ event, sports, types, existingOfficials }) {
    const { data, setData, post, processing, errors } = useForm({
        existing_official_id: '',
        sport_id: '',
        sport_category_id: '',
        sport_division_id: '',
        notes: '',
        name: '',
        email: '',
        type: '',
        certification_level: '',
        certification_expires_at: '',
    });

    const selectedSport = useMemo(
        () => sports.find((sport) => String(sport.id) === String(data.sport_id)),
        [sports, data.sport_id],
    );

    const categories = useMemo(() => {
        if (!selectedSport) {
            return [];
        }

        return selectedSport.disciplines.flatMap((discipline) =>
            discipline.categories.map((category) => ({
                ...category,
                discipline_name: discipline.name,
            })),
        );
    }, [selectedSport]);

    const divisions = useMemo(() => {
        const category = categories.find(
            (item) => String(item.id) === String(data.sport_category_id),
        );

        return category?.divisions ?? [];
    }, [categories, data.sport_category_id]);

    const usingExisting = Boolean(data.existing_official_id);

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.events.officials.store', event.id));
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
                { label: 'Register' },
            ]}
            header={<h2 className="text-xl font-semibold">Register Official</h2>}
        >
            <Head title={`Register Official — ${event.name}`} />

            <div className="mx-auto max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle>New registration for {event.name}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            {existingOfficials.length > 0 && (
                                <div>
                                    <Label htmlFor="existing_official_id">
                                        Existing official (optional)
                                    </Label>
                                    <Select
                                        value={data.existing_official_id || ''}
                                        onValueChange={(value) => {
                                            setData('existing_official_id', value);
                                            if (value) {
                                                const official = existingOfficials.find(
                                                    (item) =>
                                                        String(item.id) === value,
                                                );
                                                if (official) {
                                                    setData('name', official.name);
                                                    setData('email', official.email || '');
                                                    setData('type', official.type);
                                                }
                                            }
                                        }}
                                    >
                                        <SelectTrigger id="existing_official_id">
                                            <SelectValue placeholder="Create new profile" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">
                                                Create new profile
                                            </SelectItem>
                                            {existingOfficials.map((official) => (
                                                <SelectItem
                                                    key={official.id}
                                                    value={String(official.id)}
                                                >
                                                    {official.name}
                                                    {official.email
                                                        ? ` (${official.email})`
                                                        : ''}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.existing_official_id} />
                                </div>
                            )}

                            <div>
                                <Label htmlFor="sport_id">Sport</Label>
                                <Select
                                    value={data.sport_id || ''}
                                    onValueChange={(value) => {
                                        setData({
                                            ...data,
                                            sport_id: value,
                                            sport_category_id: '',
                                            sport_division_id: '',
                                        });
                                    }}
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

                            {categories.length > 0 && (
                                <div>
                                    <Label htmlFor="sport_category_id">
                                        Category (optional)
                                    </Label>
                                    <Select
                                        value={data.sport_category_id || ''}
                                        onValueChange={(value) => {
                                            setData({
                                                ...data,
                                                sport_category_id: value,
                                                sport_division_id: '',
                                            });
                                        }}
                                    >
                                        <SelectTrigger id="sport_category_id">
                                            <SelectValue placeholder="Select category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">
                                                No category
                                            </SelectItem>
                                            {categories.map((category) => (
                                                <SelectItem
                                                    key={category.id}
                                                    value={String(category.id)}
                                                >
                                                    {category.discipline_name} —{' '}
                                                    {category.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.sport_category_id} />
                                </div>
                            )}

                            {divisions.length > 0 && (
                                <div>
                                    <Label htmlFor="sport_division_id">
                                        Division (optional)
                                    </Label>
                                    <Select
                                        value={data.sport_division_id || ''}
                                        onValueChange={(value) =>
                                            setData('sport_division_id', value)
                                        }
                                    >
                                        <SelectTrigger id="sport_division_id">
                                            <SelectValue placeholder="Select division" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">
                                                No division
                                            </SelectItem>
                                            {divisions.map((division) => (
                                                <SelectItem
                                                    key={division.id}
                                                    value={String(division.id)}
                                                >
                                                    {division.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.sport_division_id} />
                                </div>
                            )}

                            {!usingExisting && (
                                <>
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
                                                message={
                                                    errors.certification_expires_at
                                                }
                                            />
                                        </div>
                                    </div>
                                </>
                            )}

                            <div>
                                <Label htmlFor="notes">Notes</Label>
                                <Input
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) =>
                                        setData('notes', e.target.value)
                                    }
                                />
                                <InputError message={errors.notes} />
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Register Official
                                </Button>
                                <Button
                                    variant="outline"
                                    render={
                                        <Link
                                            href={route(
                                                'admin.events.officials.index',
                                                event.id,
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