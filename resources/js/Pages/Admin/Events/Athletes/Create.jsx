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

export default function Create({ event, sports, genders, existingAthletes }) {
    const { data, setData, post, processing, errors } = useForm({
        existing_athlete_id: '',
        sport_id: '',
        sport_category_id: '',
        sport_division_id: '',
        notes: '',
        name: '',
        dob: '',
        gender: '',
        nationality: '',
        id_number: '',
        medical_clearance: false,
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

    const usingExisting = Boolean(data.existing_athlete_id);

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.events.athletes.store', event.id));
    };

    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                {
                    label: 'Athletes',
                    href: route('admin.events.athletes.index', event.id),
                },
                { label: 'Register' },
            ]}
            header={<h2 className="text-xl font-semibold">Register Athlete</h2>}
        >
            <Head title={`Register Athlete — ${event.name}`} />

            <div className="mx-auto max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle>New registration for {event.name}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            {existingAthletes.length > 0 && (
                                <div>
                                    <Label htmlFor="existing_athlete_id">
                                        Existing athlete (optional)
                                    </Label>
                                    <Select
                                        value={data.existing_athlete_id || ''}
                                        onValueChange={(value) => {
                                            setData('existing_athlete_id', value);
                                            if (value) {
                                                const athlete = existingAthletes.find(
                                                    (item) =>
                                                        String(item.id) === value,
                                                );
                                                if (athlete) {
                                                    setData('name', athlete.name);
                                                }
                                            }
                                        }}
                                    >
                                        <SelectTrigger id="existing_athlete_id">
                                            <SelectValue placeholder="Create new profile" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">
                                                Create new profile
                                            </SelectItem>
                                            {existingAthletes.map((athlete) => (
                                                <SelectItem
                                                    key={athlete.id}
                                                    value={String(athlete.id)}
                                                >
                                                    {athlete.name}
                                                    {athlete.id_number
                                                        ? ` (${athlete.id_number})`
                                                        : ''}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.existing_athlete_id} />
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

                                    <div className="grid gap-4 sm:grid-cols-2">
                                        <div>
                                            <Label htmlFor="dob">
                                                Date of birth
                                            </Label>
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
                                            <InputError
                                                message={errors.nationality}
                                            />
                                        </div>
                                        <div>
                                            <Label htmlFor="id_number">
                                                ID number
                                            </Label>
                                            <Input
                                                id="id_number"
                                                value={data.id_number}
                                                onChange={(e) =>
                                                    setData(
                                                        'id_number',
                                                        e.target.value,
                                                    )
                                                }
                                            />
                                            <InputError
                                                message={errors.id_number}
                                            />
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
                                    <InputError
                                        message={errors.medical_clearance}
                                    />
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
                                    Register Athlete
                                </Button>
                                <Button
                                    variant="outline"
                                    render={
                                        <Link
                                            href={route(
                                                'admin.events.athletes.index',
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