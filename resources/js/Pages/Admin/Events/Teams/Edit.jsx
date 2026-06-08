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

export default function Edit({ event, team, organizationMembers }) {
    const { data, setData, put, processing, errors } = useForm({
        name: team.name,
        slug: team.slug,
        coach_user_id: team.coach?.id ? String(team.coach.id) : '',
        manager_user_id: team.manager?.id ? String(team.manager.id) : '',
        notes: team.notes || '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.events.teams.update', [event.id, team.id]));
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                {
                    label: 'Teams',
                    href: route('admin.events.teams.index', event.id),
                },
                {
                    label: team.name,
                    href: route('admin.events.teams.show', [event.id, team.id]),
                },
                { label: 'Edit' },
            ]}
            header={<h2 className="text-xl font-semibold">Edit Team</h2>}
        >
            <Head title={`Edit ${team.name}`} />

            <div className="mx-auto max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle>Update team</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
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
                                    Save Changes
                                </Button>
                                <Button
                                    variant="outline"
                                    render={
                                        <Link
                                            href={route(
                                                'admin.events.teams.show',
                                                [event.id, team.id],
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