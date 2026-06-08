import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Badge } from '@/components/ui/badge';
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
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';

export default function Show({ event, sport, genders }) {
    const { flash } = usePage().props;

    const disciplineForm = useForm({ name: '' });
    const categoryForm = useForm({
        discipline_id: '',
        name: '',
        gender: 'open',
        min_age: '',
        max_age: '',
    });
    const divisionForm = useForm({
        discipline_id: '',
        category_id: '',
        name: '',
    });

    const addDiscipline = (e) => {
        e.preventDefault();
        disciplineForm.post(
            route('admin.events.sports.disciplines.store', [
                event.id,
                sport.id,
            ]),
            { preserveScroll: true, onSuccess: () => disciplineForm.reset() },
        );
    };

    const addCategory = (e) => {
        e.preventDefault();
        categoryForm.post(
            route('admin.events.sports.categories.store', [
                event.id,
                sport.id,
                categoryForm.data.discipline_id,
            ]),
            { preserveScroll: true, onSuccess: () => categoryForm.reset() },
        );
    };

    const addDivision = (e) => {
        e.preventDefault();
        divisionForm.post(
            route('admin.events.sports.divisions.store', [
                event.id,
                sport.id,
                divisionForm.data.discipline_id,
                divisionForm.data.category_id,
            ]),
            { preserveScroll: true, onSuccess: () => divisionForm.reset() },
        );
    };

    return (
        <AuthenticatedLayout
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                {
                    label: 'Sports',
                    href: route('admin.events.sports.index', event.id),
                },
                { label: sport.name },
            ]}
            header={
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-xl font-semibold">{sport.name}</h2>
                        <p className="text-sm text-muted-foreground">
                            {sport.template_slug
                                ? `Template: ${sport.template_slug}`
                                : 'Custom sport'}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Badge>{sport.status}</Badge>
                        <Button
                            variant="outline"
                            render={
                                <Link
                                    href={route('admin.events.sports.edit', [
                                        event.id,
                                        sport.id,
                                    ])}
                                />
                            }
                        >
                            Edit
                        </Button>
                    </div>
                </div>
            }
        >
            <Head title={sport.name} />

            <div className="mx-auto max-w-5xl space-y-6">
                {flash?.success && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Disciplines</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <form
                            onSubmit={addDiscipline}
                            className="flex flex-col gap-3 sm:flex-row sm:items-end"
                        >
                            <div className="flex-1">
                                <Label htmlFor="discipline_name">New discipline</Label>
                                <Input
                                    id="discipline_name"
                                    value={disciplineForm.data.name}
                                    onChange={(e) =>
                                        disciplineForm.setData(
                                            'name',
                                            e.target.value,
                                        )
                                    }
                                    placeholder="e.g. Freestyle"
                                />
                            </div>
                            <Button
                                type="submit"
                                disabled={disciplineForm.processing}
                            >
                                Add discipline
                            </Button>
                        </form>

                        {sport.disciplines.length === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No disciplines yet. Add one or use a template
                                when creating the sport.
                            </p>
                        ) : (
                            sport.disciplines.map((discipline) => (
                                <div
                                    key={discipline.id}
                                    className="space-y-3 rounded-lg border p-4"
                                >
                                    <div className="flex items-center justify-between">
                                        <h3 className="font-semibold">
                                            {discipline.name}
                                        </h3>
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            onClick={() =>
                                                router.delete(
                                                    route(
                                                        'admin.events.sports.disciplines.destroy',
                                                        [
                                                            event.id,
                                                            sport.id,
                                                            discipline.id,
                                                        ],
                                                    ),
                                                    { preserveScroll: true },
                                                )
                                            }
                                        >
                                            Remove
                                        </Button>
                                    </div>

                                    {discipline.categories.map((category) => (
                                        <div
                                            key={category.id}
                                            className="ml-4 space-y-2 border-l pl-4"
                                        >
                                            <div className="flex items-center justify-between">
                                                <div>
                                                    <p className="font-medium">
                                                        {category.name}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {category.gender}
                                                    </p>
                                                </div>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    onClick={() =>
                                                        router.delete(
                                                            route(
                                                                'admin.events.sports.categories.destroy',
                                                                [
                                                                    event.id,
                                                                    sport.id,
                                                                    discipline.id,
                                                                    category.id,
                                                                ],
                                                            ),
                                                            { preserveScroll: true },
                                                        )
                                                    }
                                                >
                                                    Remove
                                                </Button>
                                            </div>

                                            <div className="flex flex-wrap gap-2">
                                                {category.divisions.map(
                                                    (division) => (
                                                        <Badge
                                                            key={division.id}
                                                            variant="secondary"
                                                            className="gap-2"
                                                        >
                                                            {division.name}
                                                            <button
                                                                type="button"
                                                                className="text-xs"
                                                                onClick={() =>
                                                                    router.delete(
                                                                        route(
                                                                            'admin.events.sports.divisions.destroy',
                                                                            [
                                                                                event.id,
                                                                                sport.id,
                                                                                discipline.id,
                                                                                category.id,
                                                                                division.id,
                                                                            ],
                                                                        ),
                                                                        {
                                                                            preserveScroll: true,
                                                                        },
                                                                    )
                                                                }
                                                            >
                                                                ×
                                                            </button>
                                                        </Badge>
                                                    ),
                                                )}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            ))
                        )}
                    </CardContent>
                </Card>

                {sport.disciplines.length > 0 && (
                    <>
                        <Card>
                            <CardHeader>
                                <CardTitle>Add category</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form
                                    onSubmit={addCategory}
                                    className="grid gap-3 sm:grid-cols-2"
                                >
                                    <div>
                                        <Label>Discipline</Label>
                                        <Select
                                            value={categoryForm.data.discipline_id}
                                            onValueChange={(value) =>
                                                categoryForm.setData(
                                                    'discipline_id',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select discipline" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {sport.disciplines.map(
                                                    (discipline) => (
                                                        <SelectItem
                                                            key={discipline.id}
                                                            value={String(
                                                                discipline.id,
                                                            )}
                                                        >
                                                            {discipline.name}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label>Category name</Label>
                                        <Input
                                            value={categoryForm.data.name}
                                            onChange={(e) =>
                                                categoryForm.setData(
                                                    'name',
                                                    e.target.value,
                                                )
                                            }
                                        />
                                    </div>
                                    <div>
                                        <Label>Gender</Label>
                                        <Select
                                            value={categoryForm.data.gender}
                                            onValueChange={(value) =>
                                                categoryForm.setData(
                                                    'gender',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue />
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
                                    </div>
                                    <div className="flex items-end">
                                        <Button type="submit" className="w-full">
                                            Add category
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Add division</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <form
                                    onSubmit={addDivision}
                                    className="grid gap-3 sm:grid-cols-3"
                                >
                                    <div>
                                        <Label>Discipline</Label>
                                        <Select
                                            value={divisionForm.data.discipline_id}
                                            onValueChange={(value) => {
                                                divisionForm.setData({
                                                    discipline_id: value,
                                                    category_id: '',
                                                    name: divisionForm.data.name,
                                                });
                                            }}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select discipline" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {sport.disciplines.map(
                                                    (discipline) => (
                                                        <SelectItem
                                                            key={discipline.id}
                                                            value={String(
                                                                discipline.id,
                                                            )}
                                                        >
                                                            {discipline.name}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label>Category</Label>
                                        <Select
                                            value={divisionForm.data.category_id}
                                            onValueChange={(value) =>
                                                divisionForm.setData(
                                                    'category_id',
                                                    value,
                                                )
                                            }
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select category" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {sport.disciplines
                                                    .find(
                                                        (d) =>
                                                            String(d.id) ===
                                                            divisionForm.data
                                                                .discipline_id,
                                                    )
                                                    ?.categories.map(
                                                        (category) => (
                                                            <SelectItem
                                                                key={category.id}
                                                                value={String(
                                                                    category.id,
                                                                )}
                                                            >
                                                                {category.name}
                                                            </SelectItem>
                                                        ),
                                                    )}
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label>Division name</Label>
                                        <Input
                                            value={divisionForm.data.name}
                                            onChange={(e) =>
                                                divisionForm.setData(
                                                    'name',
                                                    e.target.value,
                                                )
                                            }
                                            placeholder="e.g. U-18"
                                        />
                                    </div>
                                    <div className="sm:col-span-3">
                                        <Button type="submit">Add division</Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>
                    </>
                )}
            </div>
        </AuthenticatedLayout>
    );
}