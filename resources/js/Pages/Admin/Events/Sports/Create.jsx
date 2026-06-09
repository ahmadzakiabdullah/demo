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

export default function Create({ event, templates, statuses }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        slug: '',
        template_slug: '',
        status: 'active',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.events.sports.store', event.id));
    };

    const onTemplateChange = (slug) => {
        setData('template_slug', slug);
        const template = templates.find((item) => item.slug === slug);
        if (template && !data.name) {
            setData('name', template.name);
        }
    };

    return (
        <AuthenticatedLayout
            event={event}
            breadcrumbs={[
                { label: 'Events', href: route('admin.events.index') },
                { label: event.name, href: route('admin.events.show', event.id) },
                {
                    label: 'Sports',
                    href: route('admin.events.sports.index', event.id),
                },
                { label: 'Create' },
            ]}
            header={
                <h2 className="text-xl font-semibold">Add Sport</h2>
            }
        >
            <Head title={`Add Sport — ${event.name}`} />

            <div className="mx-auto max-w-2xl">
                <Card>
                    <CardHeader>
                        <CardTitle>New sport for {event.name}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <Label htmlFor="template_slug">Template</Label>
                                <Select
                                    value={data.template_slug}
                                    onValueChange={onTemplateChange}
                                >
                                    <SelectTrigger id="template_slug">
                                        <SelectValue placeholder="Start from scratch or pick a template" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="">
                                            Custom (no template)
                                        </SelectItem>
                                        {templates.map((template) => (
                                            <SelectItem
                                                key={template.slug}
                                                value={template.slug}
                                            >
                                                {template.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.template_slug} />
                            </div>

                            <div>
                                <Label htmlFor="name">Name</Label>
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
                                <Label htmlFor="slug">Slug (optional)</Label>
                                <Input
                                    id="slug"
                                    value={data.slug}
                                    onChange={(e) =>
                                        setData('slug', e.target.value)
                                    }
                                />
                                <InputError message={errors.slug} />
                            </div>

                            <div>
                                <Label htmlFor="status">Status</Label>
                                <Select
                                    value={data.status}
                                    onValueChange={(value) =>
                                        setData('status', value)
                                    }
                                >
                                    <SelectTrigger id="status">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {statuses.map((status) => (
                                            <SelectItem
                                                key={status}
                                                value={status}
                                            >
                                                {status}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Create Sport
                                </Button>
                                <Button
                                    variant="outline"
                                    render={
                                        <Link
                                            href={route(
                                                'admin.events.sports.index',
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