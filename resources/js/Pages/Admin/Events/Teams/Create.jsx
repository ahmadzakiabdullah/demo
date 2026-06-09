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

export default function Create({ event, sports, participants, organizationMembers }) {
    const { data, setData, post, processing, errors } = useForm({
        event_participant_id: '',
        sport_id: '',
        name: '',
        slug: '',
        sport_category_id: '',
        sport_division_id: '',
        coach_user_id: '',
        manager_user_id: '',
        notes: '',
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

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.events.teams.store', event.id));
    };

    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                {
                    label: 'Teams',
                    href: route('admin.events.teams.index', event.id),
                },
                { label: 'Register' },
            ]}
            header={<h2 className="text-xl font-semibold">Register Team</h2>}
        >
            <Head title={`Register Team — ${event.name}`} />

            <div className="mx-auto max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle>New team for {event.name}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <Label htmlFor="event_participant_id">Participant</Label>
                                <Select
                                    value={data.event_participant_id || ''}
                                    onValueChange={(value) =>
                                        setData('event_participant_id', value)
                                    }
                                >
                                    <SelectTrigger id="event_participant_id">
                                        <SelectValue placeholder="Select participant" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {participants.map((participant) => (
                                            <SelectItem
                                                key={participant.id}
                                                value={String(participant.id)}
                                            >
                                                {participant.name}
                                                {participant.code
                                                    ? ` (${participant.code})`
                                                    : ''}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.event_participant_id} />
                            </div>

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

                            <div>
                                <Label htmlFor="name">Team name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div>
                                <Label htmlFor="slug">Slug (optional)</Label>
                                <Input
                                    id="slug"
                                    value={data.slug}
                                    onChange={(e) => setData('slug', e.target.value)}
                                />
                                <InputError message={errors.slug} />
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

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div>
                                    <Label htmlFor="coach_user_id">Coach</Label>
                                    <Select
                                        value={data.coach_user_id || ''}
                                        onValueChange={(value) =>
                                            setData('coach_user_id', value)
                                        }
                                    >
                                        <SelectTrigger id="coach_user_id">
                                            <SelectValue placeholder="Select coach" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">
                                                Not assigned
                                            </SelectItem>
                                            {organizationMembers.map((member) => (
                                                <SelectItem
                                                    key={member.id}
                                                    value={String(member.id)}
                                                >
                                                    {member.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.coach_user_id} />
                                </div>
                                <div>
                                    <Label htmlFor="manager_user_id">
                                        Team manager
                                    </Label>
                                    <Select
                                        value={data.manager_user_id || ''}
                                        onValueChange={(value) =>
                                            setData('manager_user_id', value)
                                        }
                                    >
                                        <SelectTrigger id="manager_user_id">
                                            <SelectValue placeholder="Select manager" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">
                                                Not assigned
                                            </SelectItem>
                                            {organizationMembers.map((member) => (
                                                <SelectItem
                                                    key={member.id}
                                                    value={String(member.id)}
                                                >
                                                    {member.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.manager_user_id} />
                                </div>
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
                                    Register Team
                                </Button>
                                <Button
                                    variant="outline"
                                    render={
                                        <Link
                                            href={route(
                                                'admin.events.teams.index',
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